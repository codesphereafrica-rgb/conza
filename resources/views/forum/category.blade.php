@extends('layouts.app')

@section('title', $category->name . ' - ASBL Forum')

@section('content')
    <main class="container section">
        <div class="toolbar">
            <div>
                <div class="badge">Catégorie</div>
                <h2 style="margin-top: 12px;">{{ $category->name }}</h2>
            </div>
            @auth
                @php
                    $superAdminId = \App\Models\Setting::get('super_admin_id');
                    $isAdmin = (auth()->id() == $superAdminId) || (auth()->user()->role ?? null) === 'admin';
                @endphp
                @if($isAdmin)
                    <a href="{{ route('forum.create-topic') }}" class="btn small">Nouveau sujet</a>
                @endif
            @endauth
        </div>

        <div class="card" style="margin-bottom: 24px;">
            <p class="muted">{{ $category->description ?: 'Aucune description disponible pour le moment.' }}</p>
        </div>

        <ul class="list">
            @forelse($topics as $topic)
                @php
                    $primaryPost = $topic->posts()->first();
                    $likeCount = $primaryPost ? $primaryPost->reactions()->where('type', 'like')->count() : 0;
                    $commentCount = max(0, $topic->posts()->count() - 1);
                    $thumb = null;
                    if(!empty($topic->attachments) && is_array($topic->attachments)){
                        foreach($topic->attachments as $att){
                            $fp = is_array($att) ? ($att['url'] ?? null) : (is_string($att) ? str_replace('\\','/',$att) : null);
                            if(!$fp) continue;
                            $isImage = is_array($att) ? ($att['type'] ?? null) === 'image' : preg_match('/\.(jpg|jpeg|png|gif|webp|bmp)(\?.*)?$/i', $fp);
                            $isVideo = is_array($att) ? ($att['type'] ?? null) === 'video' : preg_match('/\.(mp4|mov|avi|mkv)(\?.*)?$/i', $fp);
                            if($isImage){
                                $thumb = ['url' => is_array($att) ? $fp : Storage::disk('public')->url($fp), 'type' => 'image'];
                                break;
                            }
                            if($isVideo){
                                $thumb = ['url' => asset('images/video-preview.svg'), 'type' => 'video'];
                                break;
                            }
                        }
                    }
                @endphp

                <li class="list-item forum-post-card">
                    <div class="badge">{{ $category->name }}</div>
                    <h3 class="font-bold" style="margin: 12px 0 8px;"><a href="{{ route('forum.topic', $topic->id) }}">{{ $topic->title }}</a></h3>
                    <p class="muted forum-post-description">{{ $topic->content ?: 'Aucune description disponible.' }}</p>

                    @if($thumb)
                        <a href="{{ route('forum.topic', $topic->id) }}" class="forum-post-media-link">
                            @if($thumb['type'] === 'image')
                                <img src="{{ $thumb['url'] }}" alt="Image du sujet" class="forum-post-media">
                            @else
                                <img src="{{ $thumb['url'] }}" alt="Vidéo du sujet" class="forum-post-media">
                            @endif
                        </a>
                    @endif

                    <div class="forum-post-actions">
                        @if($primaryPost)
                            <form method="POST" action="{{ route('forum.react', $primaryPost->id) }}">
                                @csrf
                                <input type="hidden" name="type" value="like">
                                <button type="submit" class="forum-post-action">👍 Like <span>({{ $likeCount }})</span></button>
                            </form>
                        @endif
                        <a href="{{ route('forum.topic', $topic->id) }}#reponses" class="forum-post-action">💬 Commenter <span>({{ $commentCount }})</span></a>
                        <button type="button" class="forum-post-action share-btn" data-share-url="{{ route('forum.topic', $topic->id) }}" data-share-title="{{ $topic->title }}">🔗 Partager</button>
                    </div>
                </li>
            @empty
                <li class="list-item">
                    <p class="muted">Aucun sujet dans cette catégorie pour le moment.</p>
                </li>
            @endforelse
        </ul>

        <div style="margin-top: 20px;">{{ $topics->links() }}</div>
    </main>
@endsection
