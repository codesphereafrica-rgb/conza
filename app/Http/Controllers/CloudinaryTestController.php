<?php

namespace App\Http\Controllers;

use Cloudinary\Cloudinary;
use Illuminate\Http\Request;

class CloudinaryTestController extends Controller
{
    public function index()
    {
        return view('cloudinary-test');
    }

    public function upload(Request $request)
    {
        $validated = $request->validate([
            'media' => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/gif,image/webp,video/mp4', 'max:51200'],
        ]);

        $file = $validated['media'];
        $isVideo = str_starts_with((string) $file->getMimeType(), 'video/');
        $result = app(Cloudinary::class)->uploadApi()->upload($file->getRealPath(), [
            'folder' => $isVideo ? 'conza_videos' : 'conza_posts',
            'resource_type' => $isVideo ? 'video' : 'image',
        ]);

        return view('cloudinary-test', ['url' => $result->offsetGet('secure_url')]);
    }
}