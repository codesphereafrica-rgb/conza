@extends('layouts.app')

@section('title', 'Nouveau sujet - ASBL Forum')

@section('content')
    <main class="container section" style="max-width: 760px;">
        <div class="card">
            <h2>Créer un nouveau sujet</h2>

            <form method="POST" action="{{ route('forum.create-topic') }}" enctype="multipart/form-data">
                @csrf

                <div class="form-group">
                    <label for="title">Titre du sujet</label>
                    <input id="title" name="title" type="text" value="{{ old('title') }}" required>
                    @error('title')
                        <div class="alert alert-error" style="margin-top: 8px;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="category_id">Catégorie</label>
                    <select id="category_id" name="category_id">
                        <option value="">-- Aucune --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <div class="alert alert-error" style="margin-top: 8px;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="attachments">Images ou vidéos MP4</label>
                    <input id="attachments" name="attachments[]" type="file" accept="image/*,video/mp4" multiple>
                    @error('attachments')
                        <div class="alert alert-error" style="margin-top: 8px;">{{ $message }}</div>
                    @enderror
                    @error('attachments.*')
                        <div class="alert alert-error" style="margin-top: 8px;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="content">Contenu</label>
                    <textarea id="content" name="content" required>{{ old('content') }}</textarea>
                    @error('content')
                        <div class="alert alert-error" style="margin-top: 8px;">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn">Publier le sujet</button>
            </form>
        </div>
    </main>
@endsection
