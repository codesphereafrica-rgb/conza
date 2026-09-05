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
                            $filePath = is_array($attachment) ? ($attachment['url'] ?? null) : (is_string($attachment) ? str_replace('\\', '/', $attachment) : null);
                            $mediaUrl = is_array($attachment) ? $filePath : ($filePath ? Storage::disk('public')->url($filePath) : null);
                            $isImage = $filePath && (is_array($attachment) ? ($attachment['type'] ?? null) === 'image' : preg_match('/\.(jpg|jpeg|png|gif|webp|bmp)(\?.*)?$/i', $filePath));
                            $isVideo = $filePath && (is_array($attachment) ? ($attachment['type'] ?? null) === 'video' : preg_match('/\.(mp4|mov|avi|mkv)(\?.*)?$/i', $filePath));
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

        <style>
            .forum-comment-item {
                display: flex;
                flex-direction: column;
                gap: 12px;
                background: #fff;
                border: 1px solid #e5e7eb;
                border-radius: 16px;
                padding: 16px;
            }
            .forum-comment-header {
                display: flex;
                align-items: center;
                gap: 10px;
                flex-wrap: wrap;
            }
            .forum-comment-avatar {
                width: 38px;
                height: 38px;
                border-radius: 50%;
                object-fit: cover;
                background: #e2e8f0;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-weight: 700;
                color: #0f172a;
            }
            .forum-comment-user {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                font-weight: 700;
            }
            .forum-comment-meta {
                margin-left: auto;
                color: #6b7280;
                font-size: 0.8rem;
            }
            .forum-comment-body {
                margin: 0;
                line-height: 1.7;
            }
            .forum-comment-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                align-items: center;
            }
            .forum-comment-action-btn {
                border: 1px solid #e5e7eb;
                background: #f8fafc;
                color: #0f172a;
                border-radius: 999px;
                padding: 8px 12px;
                font-size: 0.82rem;
                font-weight: 700;
                cursor: pointer;
            }
            .forum-reply-inline {
                margin-top: 8px;
                padding: 8px 10px;
                border-radius: 10px;
                background: #f8fafc;
                border: 1px solid #e5e7eb;
                color: #374151;
                font-size: 0.9rem;
            }
            .forum-comment-form {
                margin-top: 24px;
                padding: 18px;
                border: 1px solid #e5e7eb;
                border-radius: 16px;
                background: #ffffff;
            }
            .forum-comment-form textarea {
                min-height: 100px;
            }
        </style>

        <h3 id="reponses">Réponses</h3>
        <ul class="list" id="reponses-list">
            @forelse($topic->posts as $post)
                <li class="forum-comment-item">
                    <div class="forum-comment-header">
                        <div class="forum-comment-user">
                            @if($post->user && $post->user->avatar)
                                <img src="{{ $post->user->avatar }}" alt="Avatar de {{ $post->user->name }}" class="forum-comment-avatar">
                            @else
                                <span class="forum-comment-avatar" aria-hidden="true">{{ strtoupper(substr(($post->user->name ?? 'U'), 0, 1)) }}</span>
                            @endif
                            <span>{{ $post->user->name }}</span>
                        </div>
                        <span class="forum-comment-meta">{{ $post->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="forum-comment-body">{{ $post->content }}</p>

                    @auth
                        <div class="forum-comment-actions">
                            <form method="POST" action="{{ route('forum.react', $post->id) }}">
                                @csrf
                                <input type="hidden" name="type" value="like">
                                <button type="submit" class="forum-comment-action-btn">👍 J'aime</button>
                            </form>
                            <button type="button" class="forum-comment-action-btn reply-to-comment" data-user-name="{{ $post->user->name }}" data-comment-id="{{ $post->id }}">💬 Répondre</button>
                        </div>
                    @endauth
                </li>
            @empty
                <li class="list-item">
                    <p class="muted">Aucune réponse pour le moment. Soyez le premier à réagir.</p>
                </li>
            @endforelse
        </ul>

        @auth
            <div class="forum-comment-form" id="comment-form">
                <h3>Ajouter une réponse</h3>
                <form method="POST" action="{{ route('forum.reply', $topic->id) }}">
                    @csrf
                    <input type="hidden" name="parent_id" id="reply-parent-id" value="">
                    <textarea id="comment-textarea" name="content" required placeholder="Écrivez une réponse..."></textarea>
                    <button type="submit" class="btn" style="margin-top: 12px;">Répondre</button>
                </form>
            </div>
        @else
            <div class="card" style="margin-top: 24px;">
                <p class="muted">Connectez-vous pour participer à la discussion.</p>
            </div>
        @endauth
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const replyButtons = document.querySelectorAll('.reply-to-comment');
            const commentField = document.getElementById('comment-textarea');
            const parentInput = document.getElementById('reply-parent-id');
            const commentForm = document.getElementById('comment-form');

            if (!replyButtons.length || !commentField || !parentInput || !commentForm) {
                return;
            }

            replyButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    const userName = button.dataset.userName || 'cette personne';
                    const commentId = button.dataset.commentId || '';
                    const prefix = '@' + userName + ' ';
                    commentField.value = (commentField.value ? commentField.value + '\n' : '') + prefix;
                    parentInput.value = commentId;
                    commentForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    commentField.focus();
                    commentField.setSelectionRange(commentField.value.length, commentField.value.length);
                });
            });
        });
    </script>
@endsection
