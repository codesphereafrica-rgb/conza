@extends('layouts.app')

@section('title', 'Nouveau sujet - ASBL Forum')

@section('content')
    <main class="container section" style="max-width: 760px;">
        <div class="card">
            <h2>Créer un nouveau sujet</h2>

            <form id="create-topic-form" method="POST" action="{{ route('forum.create-topic') }}" enctype="multipart/form-data">
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
                    <label for="attachments">Images ou vidéos</label>
                    <input id="attachments" name="attachments[]" type="file" accept="image/*,video/mp4,video/quicktime,video/x-msvideo" multiple>
                    <small class="muted">Max 1Go - MP4 recommandé</small>
                    <div id="upload-progress-wrap" hidden style="margin-top:10px;">
                        <progress id="upload-progress" value="0" max="100" style="width:100%;"></progress>
                        <small id="upload-progress-label" class="muted">Préparation de l’upload...</small>
                    </div>
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

                <button id="submit-topic" type="submit" class="btn">Publier le sujet</button>
            </form>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('create-topic-form');
            const progressWrap = document.getElementById('upload-progress-wrap');
            const progress = document.getElementById('upload-progress');
            const progressLabel = document.getElementById('upload-progress-label');
            const submitButton = document.getElementById('submit-topic');

            form.addEventListener('submit', function (event) {
                event.preventDefault();
                progressWrap.hidden = false;
                submitButton.disabled = true;

                const request = new XMLHttpRequest();
                request.open('POST', form.action);
                request.upload.addEventListener('progress', function (progressEvent) {
                    if (!progressEvent.lengthComputable) return;
                    const percent = Math.round((progressEvent.loaded / progressEvent.total) * 100);
                    progress.value = percent;
                    progressLabel.textContent = `Upload : ${percent}%`;
                });
                request.addEventListener('load', function () {
                    if (request.status >= 200 && request.status < 400) {
                        progress.value = 100;
                        progressLabel.textContent = 'Upload terminé. Ouverture du sujet...';
                        window.location.href = request.responseURL || form.action;
                        return;
                    }

                    submitButton.disabled = false;
                    progressLabel.textContent = 'Une erreur est survenue pendant l’upload.';
                });
                request.addEventListener('error', function () {
                    submitButton.disabled = false;
                    progressLabel.textContent = 'Impossible de terminer l’upload.';
                });
                request.send(new FormData(form));
            });
        });
    </script>
@endsection
