@extends('layouts.app')

@section('title', 'Test Cloudinary - Conza')

@section('content')
    <main class="container section auth-panel">
        <div class="card">
            <h2>Test Cloudinary</h2>
            <form method="POST" action="{{ route('cloudinary.test.upload') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label for="media">Image ou vidéo MP4</label>
                    <input id="media" name="media" type="file" accept="image/*,video/mp4" required>
                    @error('media')<div class="alert alert-error" style="margin-top:8px;">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn">Envoyer vers Cloudinary</button>
            </form>
            @isset($url)
                <div class="alert alert-success" style="margin-top:16px; overflow-wrap:anywhere;">
                    Upload réussi : <a href="{{ $url }}" target="_blank" rel="noopener">{{ $url }}</a>
                </div>
            @endisset
        </div>
    </main>
@endsection