<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Reaction;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ForumController extends Controller
{
    private function resizeImageToStandard(string $path, string $mimeType): void
    {
        $maxWidth = 640;
        $maxHeight = 640;

        if (!function_exists('imagecreatefromjpeg') || !function_exists('imagecreatetruecolor')) {
            return;
        }

        [$width, $height] = getimagesize($path) ?: [0, 0];

        if ($width <= 0 || $height <= 0) {
            return;
        }

        $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
        $newWidth = max(1, (int) round($width * $ratio));
        $newHeight = max(1, (int) round($height * $ratio));

        $sourceImage = match ($mimeType) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/gif' => imagecreatefromgif($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : null,
            default => null,
        };

        if ($sourceImage === null) {
            return;
        }

        $resized = imagecreatetruecolor($newWidth, $newHeight);

        if ($mimeType === 'image/png' || $mimeType === 'image/webp') {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
        }

        imagecopyresampled($resized, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
                imagejpeg($resized, $path, 80);
                break;
            case 'image/png':
                imagepng($resized, $path, 8);
                break;
            case 'image/gif':
                imagegif($resized, $path);
                break;
            case 'image/webp':
                if (function_exists('imagewebp')) {
                    imagewebp($resized, $path, 80);
                }
                break;
        }

        imagedestroy($sourceImage);
        imagedestroy($resized);
    }

    public function index()
    {
        $categories = Category::withCount('topics')->where('is_active', true)->orderBy('ordering')->get();

        $latestTopics = Topic::with(['category', 'user'])->latest()->take(10)->get();

        return view('forum.index', compact('categories', 'latestTopics'));
    }

    public function category(string $slug)
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        $topics = Topic::with(['category', 'user'])
            ->where('category_id', $category->id)
            ->latest()
            ->paginate(10);

        return view('forum.category', compact('category', 'topics'));
    }

    public function topic($id)
    {
        $topic = Topic::with(['category', 'user', 'posts.user'])->findOrFail($id);

        return view('forum.topic', compact('topic'));
    }

    public function createTopic()
    {
        $categories = Category::where('is_active', true)->get();

        return view('forum.create-topic', compact('categories'));
    }

    public function storeTopic(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'content' => ['required', 'string', 'min:10'],
            'attachments.*' => ['file', 'mimetypes:image/jpeg,image/png,image/gif,image/webp,video/mp4', 'max:51200'],
        ]);

        // Handle file uploads (images / videos)
        $attachments = null;
        if ($request->hasFile('attachments')) {
            $paths = [];
            foreach ($request->file('attachments') as $file) {
                if ($file && $file->isValid()) {
                    $mimeType = $file->getMimeType();
                    $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());
                    $filename = Str::uuid() . '.' . $extension;
                    $path = $file->storeAs('uploads', $filename, 'public');

                    if ($path && str_starts_with((string) $mimeType, 'image/')) {
                        $this->resizeImageToStandard(storage_path('app/public/' . $path), $mimeType);
                    }

                    $paths[] = $path;
                }
            }
            $attachments = $paths ?: null;
        }

        $topic = Topic::create([
            'title' => $validated['title'],
            'category_id' => $validated['category_id'] ?? null,
            'user_id' => Auth::id(),
            'content' => $validated['content'],
            'attachments' => $attachments,
        ]);

        Post::create([
            'topic_id' => $topic->id,
            'user_id' => Auth::id(),
            'content' => $validated['content'],
        ]);

        return redirect()->route('forum.topic', $topic->id)->with('success', 'Votre sujet a bien été publié.');
    }

    public function storeReply(Request $request, Topic $topic)
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'min:2'],
        ]);

        Post::create([
            'topic_id' => $topic->id,
            'user_id' => Auth::id(),
            'content' => $validated['content'],
        ]);

        return back()->with('success', 'Votre réponse a été ajoutée.');
    }

    public function react(Request $request, Post $post)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:like'],
        ]);

        $existing = Reaction::where('post_id', $post->id)
            ->where('user_id', Auth::id())
            ->where('type', $validated['type'])
            ->first();

        if ($existing) {
            return back()->with('info', 'Vous avez déjà réagi à ce message.');
        }

        Reaction::create([
            'user_id' => Auth::id(),
            'post_id' => $post->id,
            'type' => $validated['type'],
        ]);

        return back()->with('success', 'Réaction enregistrée.');
    }

    public function search(Request $request)
    {
        $query = $request->input('q');

        $results = Topic::with(['category', 'user'])
            ->when($query, function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('content', 'like', "%{$query}%");
            })
            ->latest()
            ->paginate(10);

        return view('forum.search', compact('results', 'query'));
    }

    public function destroyTopic(Topic $topic)
    {
        $user = Auth::user();

        if (!$user || ($topic->user_id !== $user->id && ($user->role ?? null) !== 'admin')) {
            abort(403, 'Vous n\'êtes pas autorisé à supprimer ce sujet.');
        }

        if (!empty($topic->attachments) && is_array($topic->attachments)) {
            foreach ($topic->attachments as $attachment) {
                if (is_string($attachment) && $attachment !== '') {
                    Storage::disk('public')->delete($attachment);
                }
            }
        }

        $topic->posts()->delete();
        $topic->delete();

        return redirect()->route('forum.index')->with('success', 'Le sujet a bien été supprimé.');
    }
}
