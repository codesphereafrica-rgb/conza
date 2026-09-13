<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        return view('notifications.index');
    }

    public function redirectToPost(Request $request, int $id)
    {
        $post = Post::find($id);
        $topicId = $post?->topic_id ?? Topic::find($id)?->id;

        if (!$topicId) {
            return redirect()->route('notifications.index', array_filter([
                'content_missing' => 1,
                'notification_id' => $request->query('notification_id'),
            ]));
        }

        return redirect()->route('forum.topic', $topicId)->withFragment('comment-' . $id);
    }
}