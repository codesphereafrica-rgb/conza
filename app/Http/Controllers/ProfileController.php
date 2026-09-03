<?php

namespace App\Http\Controllers;

use Cloudinary\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', ['user' => Auth::user()]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        $user = Auth::user();

        if ($request->hasFile('avatar')) {
            $user->avatar = app(Cloudinary::class)->uploadApi()->upload($request->file('avatar')->getRealPath(), ['folder' => 'conza_avatars'])->offsetGet('secure_url');
            $user->save();
        }

        return back()->with('success', 'Votre avatar a été mis à jour.');
    }
}