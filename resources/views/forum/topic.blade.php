@extends('layouts.app')

@section('title', $topic->title . ' - ASBL Forum')

@section('content')
    <main class="container section forum-page">
        <div class="toolbar">
            <div>
                <div class="badge">{{ $topic->category?->name ?? 'Sans catégorie' }}</div>
                <h2 style="margin-top: 12px;">{{ $topic->title }}</h2>
                <p class="topic-meta muted">Par {{ $topic->user->name }} · {{ $topic->created_at->diffForHumans() }}</p>
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

        @php
            $primaryPost = $topic->posts()->first();
            $topicLikeCount = $primaryPost ? $primaryPost->reactions()->where('type', 'like')->count() : 0;
            $topicCommentCount = max(0, $topic->posts()->count() - 1);
        @endphp

        <article class="topic-post-card topic-card">
            <div class="topic-post-header card-header topic-post-header-green border border-[#bcd9c8] rounded-t-2xl p-4 -mx-[18px] -mt-[18px]">
                <div class="topic-post-user">
                    @if($topic->user && $topic->user->avatar)
                        <img src="{{ $topic->user->avatar }}" alt="Avatar de {{ $topic->user->name }}" class="topic-post-avatar">
                    @else
                        <span class="topic-post-avatar" aria-hidden="true">{{ strtoupper(substr(($topic->user->name ?? 'U'), 0, 1)) }}</span>
                    @endif
                    <div>
                        <strong>{{ $topic->user->name }}</strong>
                        <div class="muted" style="font-size:0.82rem;">{{ $topic->created_at->diffForHumans() }}</div>
                    </div>
                </div>
            </div>

            <div class="topic-post-content post-content">
                <div class="topic-post-body topic-text post-text bg-gray-100 border border-gray-200 rounded-xl p-3">{{ $topic->content }}</div>

                @if(!empty($topic->attachments))
                    <div class="topic-post-media-wrap media-wrapper media post-media mt-3">
                        @foreach($topic->attachments as $attachment)
                            @php
                                $filePath = is_array($attachment) ? ($attachment['url'] ?? null) : (is_string($attachment) ? str_replace('\\', '/', $attachment) : null);
                                $mediaUrl = is_array($attachment) ? $filePath : ($filePath ? Storage::disk('public')->url($filePath) : null);
                                $isImage = $filePath && (is_array($attachment) ? ($attachment['type'] ?? null) === 'image' : preg_match('/\.(jpg|jpeg|png|gif|webp|bmp)(\?.*)?$/i', $filePath));
                                $isVideo = $filePath && (is_array($attachment) ? ($attachment['type'] ?? null) === 'video' : preg_match('/\.(mp4|mov|avi|mkv)(\?.*)?$/i', $filePath));
                            @endphp

                            @if($mediaUrl && $isImage)
                                <div class="topic-post-media-frame video-wrapper">
                                    <img src="{{ $mediaUrl }}" alt="Image jointe" class="topic-post-image">
                                </div>
                            @elseif($mediaUrl && $isVideo)
                                <div class="topic-post-media-frame video-wrapper">
                                    <video controls playsinline preload="metadata" class="topic-post-video">
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

            <div class="topic-post-actions border-t border-gray-200 pt-3 mt-3">
                @if($primaryPost)
                    <form method="POST" action="{{ route('forum.react', $primaryPost->id) }}">
                        @csrf
                        <input type="hidden" name="type" value="like">
                        <button type="submit" class="topic-post-action-btn">👍 J'aime <span>({{ $topicLikeCount }})</span></button>
                    </form>
                @endif
                <button type="button" class="topic-post-action-btn scroll-to-comments">💬 Commenter <span>({{ $topicCommentCount }})</span></button>
                <button type="button" class="topic-post-action-btn share-btn" data-share-url="{{ route('forum.topic', $topic->id) }}" data-share-title="{{ $topic->title }}">🔗 Partager</button>
            </div>
        </article>

        <style>
            body {
                background: #e9eef5;
            }
            .topic-meta {
                display: none;
            }
            .topic-card {
                display: flex !important;
                flex-direction: column !important;
                gap: 1px !important;
                padding: 0 !important;
                overflow: hidden !important;
                width: 93% !important;
                margin: 0 auto 12px auto !important;
                border-radius: 16px !important;
                background-color: #e5e7eb !important;
            }
            .topic-card .p-4,
            .topic-card .p-3 {
                padding-left: 0 !important;
                padding-right: 0 !important;
            }
            .topic-card .post-text {
                padding-left: 12px !important;
                padding-right: 12px !important;
            }
            .topic-card .topic-post-content {
                margin: 0 !important;
                padding: 0 !important;
            }
            .topic-card .card-header,
            .reply-card .reply-header {
                margin-bottom: 1px !important;
                background: #e6f4f1 !important;
            }
            .topic-card .post-text,
            .topic-card video {
                margin-top: 1px !important;
                width: 100% !important;
                background: #000 !important;
            }
            .topic-card,
            .topic-card .topic-post-content,
            .topic-card .post-text,
            .topic-card .media,
            .topic-card .video-wrapper {
                background: #000 !important;
            }
            .topic-card .post-text {
                color: #fff !important;
            }
            .topic-card video,
            .topic-card img,
            .topic-card .media {
                width: 100% !important;
                margin: 0 !important;
                border-radius: 0 !important;
            }
            .reply-card {
                display: flex !important;
                flex-direction: column !important;
                gap: 1px !important;
                width: 93% !important;
                margin: 0 auto 12px auto !important;
                border-radius: 16px !important;
                padding: 0 !important;
                overflow: hidden !important;
                background-color: #e5e7eb !important;
            }
            .reply-card .reply-header {
                margin: 0 !important;
                padding: 10px 12px !important;
            }
            .reply-card .reply-bubble,
            .reply-card .message-content {
                width: 100% !important;
                margin: 0 !important;
                border-radius: 0 !important;
                border-left: 0 !important;
                border-right: 0 !important;
            }
            .reply-card button {
                background-color: #115e59 !important;
                color: white !important;
                border: none !important;
            }
            .forum-page {
                background-color: #e9eef5 !important;
            }
            .topic-post-card {
                width: min(100%, 760px);
                margin: 0 auto 24px;
                background: #fff;
                border: 1px solid #d7dee5;
                border-radius: 18px;
                padding: 18px 18px 10px;
                box-shadow: 0 14px 34px rgba(15, 23, 42, 0.14), 0 3px 8px rgba(15, 23, 42, 0.08);
            }
            .topic-post-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
            }
            .topic-post-header-green,
            .forum-comment-header-green {
                background: rgba(15, 118, 110, 0.1);
                color: #115e59;
            }
            .topic-post-header-green {
                border-radius: 16px 16px 0 0;
                padding: 16px;
                margin: -18px -18px 0;
            }
            .forum-comment-header-green {
                border-radius: 16px 16px 0 0;
                padding: 12px;
                margin: 0;
            }
            .topic-post-user {
                display: flex;
                align-items: center;
                gap: 12px;
            }
            .topic-post-avatar {
                width: 44px;
                height: 44px;
                border-radius: 50%;
                object-fit: cover;
                background: #e2e8f0;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-weight: 700;
                color: #0f172a;
            }
            .topic-post-body {
                margin: 0;
                line-height: 1.7;
                color: #111827;
                background: #f3f4f6;
                border: 0;
                border-radius: 0;
                padding: 16px 18px;
            }
            .topic-post-content {
                margin: 0 -18px;
                padding: 0 18px;
                background: #f3f4f6;
            }
            .topic-post-media-wrap {
                width: 93%;
                margin: 16px auto 0;
                border-radius: 16px;
                overflow: hidden;
                background: #000;
                border: 1px solid #0f172a;
            }
            .topic-post-media-frame {
                background: #000;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .topic-post-image,
            .topic-post-video {
                width: 100%;
                display: block;
                max-height: 480px;
                object-fit: contain;
                background: #000;
            }
            .topic-post-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                align-items: center;
                padding-top: 12px;
                border-top: 1px solid #e5e7eb;
                margin-top: 0;
                background: #fff;
            }
            .topic-post-action-btn {
                border: 0;
                background: #0f766e;
                color: #fff;
                border-radius: 999px;
                padding: 8px 12px;
                font-size: 0.82rem;
                font-weight: 700;
                cursor: pointer;
            }
            .topic-post-action-btn:hover {
                background: #115e59;
            }
            .forum-comment-item {
                display: flex;
                flex-direction: column;
                gap: 6px;
                background: #fff;
                border: 1px solid #d7dee5;
                border-radius: 16px;
                padding: 16px;
                box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
            }
            .forum-comment-header {
                display: flex;
                align-items: center;
                gap: 10px;
                flex-wrap: wrap;
                margin: -16px -16px 0;
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
                background: #f3f4f6;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 12px;
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
                background: #f9fafb;
                box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
            }
            .forum-comment-form textarea {
                min-height: 100px;
            }
            .reply-highlight {
                color: #14532d;
                font-weight: 800;
            }
            .reply-editor {
                width: 100%;
                min-height: 100px;
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                background: white;
                padding: 12px 14px;
                color: #111827;
                line-height: 1.6;
                outline: none;
            }
            .reply-editor:focus {
                border-color: #16a34a;
                box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.12);
            }
            .topic-card .card-header,
            .reply-card .reply-header {
                margin-bottom: 1px !important;
                background: #e6f4f1 !important;
            }
            .topic-card .post-text,
            .topic-card video,
            .reply-card .reply-bubble {
                margin-top: 1px !important;
                width: 100% !important;
                background: #fff !important;
            }
        </style>

        <h3 id="reponses">Réponses</h3>
        <ul class="list" id="reponses-list">
            @forelse($topic->posts as $post)
                <li class="forum-comment-item reply-card">
                    <div class="forum-comment-header reply-header forum-comment-header-green border border-[#bcd9c8] rounded-t-xl p-3 -mx-4 -mt-4">
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
                    <div class="forum-comment-body message-bubble reply-bubble bg-gray-100 border border-gray-200 rounded-lg p-3">{{ $post->content }}</div>

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
            <div class="forum-comment-form bg-gray-50" id="comment-form">
                <h3>Ajouter une réponse</h3>
                <form method="POST" action="{{ route('forum.reply', $topic->id) }}" id="reply-form">
                    @csrf
                    <input type="hidden" name="parent_id" id="reply-parent-id" value="">
                    <div id="comment-editor" class="reply-editor bg-white border border-gray-200" contenteditable="true" data-placeholder="Écrivez une réponse..."></div>
                    <textarea id="comment-textarea" name="content" required hidden></textarea>
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
            const replyEditor = document.getElementById('comment-editor');
            const hiddenTextarea = document.getElementById('comment-textarea');
            const parentInput = document.getElementById('reply-parent-id');
            const commentForm = document.getElementById('comment-form');
            const replyForm = document.getElementById('reply-form');
            const commentTrigger = document.querySelector('.scroll-to-comments');

            if (replyEditor && hiddenTextarea && replyForm) {
                const syncContent = function () {
                    hiddenTextarea.value = replyEditor.innerText.trim();
                };

                replyForm.addEventListener('submit', function () {
                    syncContent();
                });

                replyEditor.addEventListener('input', function () {
                    syncContent();
                });
            }

            if (commentTrigger && commentForm && replyEditor) {
                commentTrigger.addEventListener('click', function () {
                    commentForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    replyEditor.focus();
                });
            }

            if (!replyButtons.length || !replyEditor || !parentInput || !commentForm) {
                return;
            }

            function setCaretToEnd(element) {
                const range = document.createRange();
                const selection = window.getSelection();
                range.selectNodeContents(element);
                range.collapse(false);
                selection.removeAllRanges();
                selection.addRange(range);
            }

            replyButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    const userName = button.dataset.userName || 'cette personne';
                    const commentId = button.dataset.commentId || '';
                    const prefix = '<span class="reply-highlight">@' + userName + '</span> ';

                    if (!replyEditor.innerHTML.includes('reply-highlight')) {
                        replyEditor.innerHTML = prefix;
                    }

                    parentInput.value = commentId;
                    commentForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    replyEditor.focus();
                    setCaretToEnd(replyEditor);
                    hiddenTextarea.value = replyEditor.innerText.trim();
                });
            });
        });
    </script>
@endsection
