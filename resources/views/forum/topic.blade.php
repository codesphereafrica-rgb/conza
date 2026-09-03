@extends('layouts.app')

@section('title', $topic->title . ' - ASBL Forum')

@section('content')
    <main class="container section">
        <div class="toolbar">
            <div>
                <div class="badge">{{ $topic->category?->name ?? 'Sans catégorie' }}</div>
                <h2 style="margin-top: 12px;">{{ $topic->title }}</h2>
                <p class="muted">Par {{ $topic->user->name }} · {{ $topic->created_at->diffForHumans() }}</p>
            </div>
            @auth
                <a href="{{ route('forum.index') }}" class="btn small secondary">Retour au forum</a>
                @if(auth()->id() === $topic->user_id || (auth()->user()->role ?? null) === 'admin')
                    <form method="POST" action="{{ route('forum.topic.destroy', $topic->id) }}" style="display:inline;" onsubmit="return confirm('Voulez-vous vraiment supprimer ce sujet ?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn small" style="background:#dc2626;">Supprimer</button>
                    </form>
                @endif
            @endauth
        </div>

        <div class="card" style="margin-bottom: 24px;">
            <p>{{ $topic->content }}</p>

            @if(!empty($topic->attachments))
                <div style="margin-top: 20px; display: grid; gap: 16px;">
                    @foreach($topic->attachments as $attachment)
                        @php
                            $filePath = is_string($attachment) ? str_replace('\\', '/', $attachment) : null;
                            $mediaUrl = $filePath ? Storage::disk('public')->url($filePath) : null;
                            $isImage = $filePath && preg_match('/\.(jpg|jpeg|png|gif|webp|bmp)$/i', $filePath);
                            $isVideo = $filePath && preg_match('/\.(mp4|mov|avi|mkv)$/i', $filePath);
                        @endphp

                        @if($mediaUrl && $isImage)
                            <div style="max-width: 320px; width: 100%; margin-top: 8px;">
                                <img src="{{ $mediaUrl }}" alt="Image jointe" style="width: 100%; height: auto; max-width: 320px; display: block; border-radius: 12px; border: 1px solid #e5e7eb;">
                            </div>
                        @elseif($mediaUrl && $isVideo)
                            <div style="max-width: 640px; max-height: 320px; width: 100%; margin-top: 8px; overflow: hidden; border-radius: 12px; border: 1px solid #e5e7eb; background: #000; display:flex; align-items:center; justify-content:center;">
                                <video controls playsinline preload="metadata" style="width:100%; height:100%; max-width:640px; max-height:320px; object-fit:contain; display:block; background:#000;">
                                    <source src="{{ $mediaUrl }}" type="video/mp4">
                                    Votre navigateur ne supporte pas la lecture vidéo.
                                </video>
                            </div>
                        @elseif($mediaUrl)
                            <a href="{{ $mediaUrl }}" target="_blank" rel="noopener" class="btn small secondary">Télécharger la pièce jointe</a>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>

        <h3>Réponses</h3>
        <ul class="list">
            @forelse($topic->posts as $post)
                <li class="list-item">
                    <div style="display: flex; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
                        <strong>{{ $post->user->name }}</strong>
                        <span class="muted">{{ $post->created_at->diffForHumans() }}</span>
                    </div>
                    <p>{{ $post->content }}</p>
                    @auth
                        <form method="POST" action="{{ route('forum.react', $post->id) }}">
                            @csrf
                            <input type="hidden" name="type" value="like">
                            <button type="submit" class="btn small secondary">👍 J'aime</button>
                        </form>
                    @endauth
                </li>
            @empty
                <li class="list-item">
                    <p class="muted">Aucune réponse pour le moment. Soyez le premier à réagir.</p>
                </li>
            @endforelse
        </ul>

        @auth
            <div class="card" style="margin-top: 24px;">
                <h3>Ajouter une réponse</h3>
                <form method="POST" action="{{ route('forum.reply', $topic->id) }}">
                    @csrf
                    <textarea name="content" required></textarea>
                    <button type="submit" class="btn" style="margin-top: 12px;">Répondre</button>
                </form>
            </div>
        @else
            <div class="card" style="margin-top: 24px;">
                <p class="muted">Connectez-vous pour participer à la discussion.</p>
            </div>
        @endauth
    </main>
@endsection
