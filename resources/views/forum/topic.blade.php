@extends('layouts.app')

@section('title', $topic->title . ' - ASBL Forum')

@section('content')
    <main class="container section forum-page">
        <div class="toolbar">
            <div>
                <div class="badge">{{ $topic->category?->name ?? 'Sans catégorie' }}</div>
                <h2 class="topic-title" style="margin-top: 12px;">{{ $topic->title }}</h2>
                <p class="topic-meta muted">Par {{ $topic->user->name }} · {{ $topic->created_at->diffForHumans() }}</p>
            </div>
            @auth
                <a href="{{ route('forum.index') }}" class="btn small secondary retour-button">Retour au forum</a>
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
            $topicLikeCount = $topic->reactions()->where('type', 'like')->count();
            $topicCommentCount = $topic->posts->count();
        @endphp

        <article class="topic-post-card topic-card">
            <div class="topic-post-time muted">{{ $topic->created_at->diffForHumans() }}</div>

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
                                    <img src="{{ $mediaUrl }}" alt="Image jointe" class="topic-post-image topic-lightbox-image">
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

            <div class="topic-post-actions card-actions border-t border-gray-200 pt-3 mt-3">
                <form method="POST" action="{{ route('forum.topic.react', $topic->id) }}">
                    @csrf
                    <input type="hidden" name="type" value="like">
                    <button type="submit" class="topic-post-action-btn">👍 J'aime <span>({{ $topicLikeCount }})</span></button>
                </form>
                <button type="button" class="topic-post-action-btn scroll-to-comments">💬 Commenter <span>({{ $topicCommentCount }})</span></button>
                <button type="button" class="topic-post-action-btn share-btn" data-share-url="{{ route('forum.topic', $topic->id) }}" data-share-title="{{ $topic->title }}">🔗 Partager</button>
            </div>
        </article>

        <style>
            body,
            .page-background,
            main,
            .forum-page {
                background-color: #f4f7fb !important;
                background: #f4f7fb !important;
            }
            .forum-title,
            .topic-title,
            .category-title,
            .forum-page h1 {
                color: white !important;
            }
            .topic-title {
                color: #115e59 !important;
                font-size: 26px !important;
                font-weight: 700 !important;
                margin-top: 6px !important;
            }
            .badge,
            .category-badge {
                background-color: #115e59 !important;
                color: #b8f0df !important;
                border: 1px solid #b8f0df !important;
            }
            .retour-button {
                background-color: white !important;
                color: #1f2937 !important;
            }
            .topic-meta {
                display: none;
            }
            .home-section-title,
            .forum-section-title,
            #reponses {
                font-size: 26px !important;
                font-weight: 700 !important;
            }
            .home-section-title,
            .forum-section-title {
                margin-top: 6px !important;
            }
            .topic-lightbox-image { cursor: zoom-in; }
            .topic-card {
                display: flex !important;
                flex-direction: column !important;
                gap: 1px !important;
                padding: 0 !important;
                overflow: hidden !important;
                width: 93% !important;
                margin: 0 auto 12px auto !important;
                border-radius: 0 0 16px 16px !important;
                background-color: #e5e7eb !important;
                box-shadow: 0 12px 36px rgba(0, 0, 0, 0.42), 0 5px 14px rgba(0, 0, 0, 0.34) !important;
            }
            .topic-card,
            .reply-card {
                box-shadow: 0 8px 30px rgba(0, 0, 0, 0.35), 0 4px 10px rgba(0, 0, 0, 0.3) !important;
                border: 1px solid rgba(255, 255, 255, 0.1) !important;
            }
            .topic-card .p-4,
            .topic-card .p-3 {
                padding-left: 0 !important;
                padding-right: 0 !important;
            }
            .topic-card .post-text {
                padding: 12px !important;
                padding-left: 12px !important;
                padding-right: 12px !important;
                margin: 0 !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }
            .topic-card .topic-post-content {
                margin: 0 !important;
                padding: 0 !important;
            }
            .topic-post-time {
                align-self: flex-end;
                padding: 10px 14px 0;
                font-size: 0.82rem;
            }
            .topic-card .card-actions {
                background: white !important;
            }
            .topic-card .card-header {
                margin-bottom: 1px !important;
                padding-bottom: 12px !important;
                background-color: #b8f0df !important;
                background: #b8f0df !important;
            }
            .topic-card .post-text {
                margin-top: 1px !important;
                width: 100% !important;
                background: white !important;
            }
            .topic-card,
            .topic-card .topic-post-content {
                background: white !important;
            }
            .topic-card video,
            .topic-card img,
            .topic-card .video-container,
            .topic-card .video-wrapper,
            .topic-card .media,
            .topic-card .post-media {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                border-radius: 0 !important;
                border: none !important;
                background: black !important;
                display: block !important;
                max-width: 100% !important;
                box-sizing: border-box !important;
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
                background-color: #b8f0df !important;
                background: #b8f0df !important;
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
                margin: 0;
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
                margin: 0;
                padding: 0;
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
                margin: 0;
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
            .topic-card .topic-post-header {
                margin: 0 !important;
                padding: 16px !important;
            }
            .reply-card .reply-header {
                margin: 0 !important;
                padding: 10px 12px !important;
            }
            .reply-header-main {
                display: flex;
                align-items: center;
                justify-content: space-between;
                width: 100%;
                gap: 10px;
            }
            .comment-menu { position: relative; margin-left: auto; }
            .reply-card button.comment-menu-toggle {
                border: 0;
                background: transparent !important;
                background-color: transparent !important;
                color: #14532d;
                font-size: 1.25rem;
                line-height: 1;
                padding: 2px 5px;
                cursor: pointer;
                opacity: 1;
            }
            .reply-card button.comment-menu-toggle:hover {
                background: transparent !important;
                background-color: transparent !important;
                color: #14532d;
            }
            .reply-card button.comment-menu-toggle:focus,
            .reply-card button.comment-menu-toggle:focus-visible,
            .reply-card button.comment-menu-toggle:active {
                background: transparent !important;
                background-color: transparent !important;
                color: #14532d;
            }
            .comment-menu-dropdown {
                position: absolute;
                top: calc(100% + 4px);
                right: 0;
                z-index: 20;
                min-width: 110px;
                padding: 4px;
                background: #fff;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                box-shadow: 0 8px 20px rgba(15, 23, 42, .18);
            }
            .comment-menu-dropdown button {
                width: 100%;
                border: 0;
                border-radius: 6px;
                padding: 8px 10px;
                background: transparent;
                color: #dc2626;
                text-align: left;
                cursor: pointer;
            }
            .comment-menu-dropdown button:hover { background: #fef2f2; }
            .topic-lightbox {
                position: fixed;
                inset: 0;
                z-index: 10000;
                display: none;
                align-items: center;
                justify-content: center;
                padding: 24px;
                background: rgba(0, 0, 0, 0.96);
            }
            .topic-lightbox.is-open { display: flex; }
            .topic-lightbox img {
                max-width: 95vw;
                max-height: 90vh;
                object-fit: contain;
                cursor: zoom-in;
                transition: transform .2s ease;
            }
            .topic-lightbox.is-zoomed img {
                max-width: none;
                max-height: none;
                width: 150vw;
                height: 150vh;
                cursor: zoom-out;
            }
            .topic-lightbox-close,
            .topic-lightbox-zoom {
                position: absolute;
                top: 18px;
                border: 0;
                border-radius: 50%;
                width: 42px;
                height: 42px;
                background: rgba(255,255,255,.16);
                color: #fff;
                font-size: 1.4rem;
                cursor: pointer;
            }
            .topic-lightbox-close { right: 18px; }
            .topic-lightbox-zoom { right: 68px; }
        </style>

        <h3 id="reponses">Réponses</h3>
        <ul class="list" id="reponses-list">
            @forelse($topic->posts as $commentNumber => $post)
                <li id="comment-{{ $post->id }}" class="forum-comment-item reply-card">
                    <div class="forum-comment-header reply-header forum-comment-header-green border border-[#bcd9c8] rounded-t-xl">
                        <div class="reply-header-main">
                            <div class="forum-comment-user">
                                @if($post->user && $post->user->avatar)
                                    <img src="{{ $post->user->avatar }}" alt="Avatar de {{ $post->user->name }}" class="forum-comment-avatar">
                                @else
                                    <span class="forum-comment-avatar" aria-hidden="true">{{ strtoupper(substr(($post->user->name ?? 'U'), 0, 1)) }}</span>
                                @endif
                                <span>{{ $post->user->name }}</span>
                            </div>
                            <div class="comment-menu">
                                <span class="forum-comment-meta">Commentaire {{ $commentNumber + 1 }} · {{ $post->created_at->diffForHumans() }}</span>
                                @auth
                                    @if(auth()->id() === $post->user_id)
                                        <button type="button" class="comment-menu-toggle" aria-label="Options du commentaire" aria-expanded="false">⋮</button>
                                        <div class="comment-menu-dropdown" hidden>
                                            <form method="POST" action="{{ route('forum.comment.destroy', $post->id) }}" onsubmit="return confirm('Voulez-vous vraiment supprimer ce commentaire ?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit">Supprimer</button>
                                            </form>
                                        </div>
                                    @endif
                                @endauth
                            </div>
                        </div>
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
            const images = document.querySelectorAll('.topic-lightbox-image');
            if (!images.length) return;

            const lightbox = document.createElement('div');
            lightbox.className = 'topic-lightbox';
            lightbox.innerHTML = '<button type="button" class="topic-lightbox-zoom" aria-label="Zoomer">+</button>'
                + '<button type="button" class="topic-lightbox-close" aria-label="Fermer">&times;</button>'
                + '<img alt="Image agrandie">';
            document.body.appendChild(lightbox);

            const preview = lightbox.querySelector('img');
            const closeButton = lightbox.querySelector('.topic-lightbox-close');
            const zoomButton = lightbox.querySelector('.topic-lightbox-zoom');
            const close = function () {
                lightbox.classList.remove('is-open', 'is-zoomed');
                preview.removeAttribute('src');
            };
            const toggleZoom = function () { lightbox.classList.toggle('is-zoomed'); };

            images.forEach((image) => image.addEventListener('click', function () {
                preview.src = image.src;
                preview.alt = image.alt;
                lightbox.classList.add('is-open');
            }));
            closeButton.addEventListener('click', close);
            zoomButton.addEventListener('click', toggleZoom);
            preview.addEventListener('click', toggleZoom);
            lightbox.addEventListener('click', function (event) {
                if (event.target === lightbox) close();
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') close();
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.comment-menu-toggle').forEach(function (toggle) {
                toggle.addEventListener('click', function (event) {
                    event.stopPropagation();
                    const menu = toggle.nextElementSibling;
                    const isOpen = !menu.hidden;
                    document.querySelectorAll('.comment-menu-dropdown').forEach((item) => { item.hidden = true; });
                    document.querySelectorAll('.comment-menu-toggle').forEach((item) => { item.setAttribute('aria-expanded', 'false'); });
                    menu.hidden = isOpen;
                    toggle.setAttribute('aria-expanded', String(!isOpen));
                });
            });

            document.addEventListener('click', function () {
                document.querySelectorAll('.comment-menu-dropdown').forEach((item) => { item.hidden = true; });
                document.querySelectorAll('.comment-menu-toggle').forEach((item) => { item.setAttribute('aria-expanded', 'false'); });
            });

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
