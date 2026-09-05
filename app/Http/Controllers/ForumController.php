<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Reaction;
use App\Models\Topic;
use Cloudinary\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ForumController extends Controller
{
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

        $attachments = null;
        if ($request->hasFile('attachments')) {
            $cloudinary = app(Cloudinary::class);
            $uploaded = [];

            foreach ($request->file('attachments') as $file) {
                if ($file && $file->isValid()) {
                    $isVideo = str_starts_with((string) $file->getMimeType(), 'video/');
                    $result = $cloudinary->uploadApi()->upload($file->getRealPath(), [
                        'folder' => $isVideo ? 'conza_videos' : 'conza_posts',
                        'resource_type' => $isVideo ? 'video' : 'image',
                    ]);

                    $uploaded[] = [
                        'url' => $result->offsetGet('secure_url'),
                        'type' => $isVideo ? 'video' : 'image',
                    ];
                }
            }
            $attachments = $uploaded ?: null;
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
            'parent_id' => ['nullable', 'exists:posts,id'],
        ]);

        $parentPost = null;
        if (!empty($validated['parent_id'])) {
            $parentPost = Post::where('id', $validated['parent_id'])
                ->where('topic_id', $topic->id)
                ->first();
        }

        Post::create([
            'topic_id' => $topic->id,
            'user_id' => Auth::id(),
            'parent_id' => $parentPost?->id,
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

        $topic->posts()->delete();
        $topic->delete();

        return redirect()->route('forum.index')->with('success', 'Le sujet a bien été supprimé.');
    }
}
