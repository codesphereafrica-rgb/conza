@extends('layouts.app')

@section('title', 'Accueil - Conza ASBL')

@section('content')
    <section class="hero">
        <div class="container hero-content">
            <div class="hero-top">
                <div class="hero-text-column">
                    <h1>Collectif citoyen de la communauté Nationale du Congo-Zaïre</h1>
                    <p>
                        Un espace communautaire pour partager des idées, débattre, informer et soutenir les projets
                        qui portent la mission de notre association.
                    </p>
                    <div class="hero-actions">
                        <a href="{{ route('forum.index') }}" class="btn">Explorer le forum</a>
                        <a href="{{ route('donations.index') }}" class="btn secondary">Voir les dons</a>
                    </div>
                </div>
                <div class="hero-right-visuals" aria-hidden="true">
                    @php
                        $homeSlides = [];
                        $imageDirectory = public_path('images_conza');
                        $allowedImageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];

                        if (is_dir($imageDirectory)) {
                            $files = glob($imageDirectory . DIRECTORY_SEPARATOR . '*');

                            if (is_array($files)) {
                                foreach ($files as $file) {
                                    if (!is_file($file)) {
                                        continue;
                                    }

                                    $filename = basename($file);
                                    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

                                    if (preg_match('/logo/i', $filename)) {
                                        continue;
                                    }

                                    if (in_array($extension, $allowedImageExtensions, true)) {
                                        $homeSlides[] = '/images_conza/' . $filename;
                                    }
                                }
                            }
                        }

                        sort($homeSlides, SORT_STRING);

                        if (empty($homeSlides)) {
                            $homeSlides = [
                                '/images_conza/image_fond_conza.png',
                                '/images_conza/ancetres_congolais.jpeg',
                                '/images_conza/Chutes_de_la_Lofoï.jpg',
                            ];
                        }
                    @endphp
                    @foreach($homeSlides as $index => $slide)
                        <img
                            class="hero-visual{{ $index === 0 ? ' is-active' : '' }}"
                            src="{{ url($slide) }}"
                            alt=""
                            aria-hidden="true"
                        >
                    @endforeach
                    <div class="hero-visual-overlay"></div>
                </div>
            </div>
        </div>
    </section>

    <main class="container section">
        <div class="grid grid-3">
            <div class="card card-with-media">
                <img src="{{ url('/images_conza/notre_mission.png') }}" alt="Notre mission">
                <h3>Notre mission</h3>
                <p class="muted">Maintenir la cohésion sociale ainsi que l'unité nationale du peuple du Congo-Zaïre d'ici et d'ailleurs.</p>
            </div>
            <div class="card card-with-media">
                <img src="{{ url('/images_conza/notre_vision.png') }}" alt="Notre vision">
                <h3>Notre vision</h3>
                <p class="muted">Créer et Maintenir la communauté nationale repondant au rendez-vous du donner et du recevoir.</p>
            </div>
            <div class="card card-with-media">
                <img src="{{ url('/images_conza/nos_objectifs.png') }}" alt="Nos objectifs">
                <h3>Nos objectifs</h3>
                <ul class="muted objective-list">
                    <li>Assumer la qualité du Souverain primaire;</li>
                    <li>Amener le maximum de la population à assumer démocratiquement l'Etat National Moderne du bassin du Grand Congo.</li>
                </ul>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const slides = Array.from(document.querySelectorAll('.hero-visual'));

            if (slides.length < 2) {
                return;
            }

            let activeIndex = 0;
            window.setInterval(function () {
                slides[activeIndex].classList.remove('is-active');
                activeIndex = (activeIndex + 1) % slides.length;
                slides[activeIndex].classList.add('is-active');
            }, 10000);
        });
    </script>

    <section class="container section" style="padding-top: 0;">
        <div class="toolbar">
            <h2>Dernières discussions</h2>
            <a href="{{ route('forum.index') }}" class="btn small">Voir tout</a>
        </div>

        <ul class="list">
            @forelse($latestTopics as $topic)
                <li class="list-item">
                    <div class="badge">{{ $topic->category->name ?? 'Forum' }}</div>
                    <h3 style="margin: 12px 0 8px;">
                        <a href="{{ route('forum.topic', $topic->id) }}">{{ $topic->title }}</a>
                    </h3>
                    <p class="muted">{{ $topic->user->name ?? 'Membre' }} · {{ $topic->created_at->diffForHumans() }}</p>

                    @php
                        $thumb = null;
                        if(!empty($topic->attachments) && is_array($topic->attachments)){
                            foreach($topic->attachments as $att){
                                $fp = is_string($att) ? str_replace('\\', '/', $att) : null;
                                if(!$fp) continue;
                                $isImage = preg_match('/\.(jpg|jpeg|png|gif|webp|bmp)$/i', $fp);
                                $isVideo = preg_match('/\.(mp4|mov|avi|mkv)$/i', $fp);
                                if($isImage){
                                    $thumb = ['url' => Storage::disk('public')->url($fp), 'type' => 'image'];
                                    break;
                                }
                                if($isVideo){
                                    $thumb = ['url' => asset('images/video-preview.svg'), 'type' => 'video'];
                                    break;
                                }
                            }
                        }
                    @endphp

                    @if($thumb)
                        <div style="margin-top:8px;">
                            <a href="{{ route('forum.topic', $topic->id) }}">
                                @if($thumb['type'] === 'image')
                                    <img src="{{ $thumb['url'] }}" alt="Miniature du sujet" style="width:130px;height:130px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;display:block;">
                                @else
                                    <img src="{{ $thumb['url'] }}" alt="Miniature vidéo du sujet" style="width:130px;height:130px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;display:block;">
                                @endif
                            </a>
                        </div>
                    @endif
                </li>
            @empty
                <li class="list-item">
                    <p class="muted">Aucune discussion pour le moment.</p>
                </li>
            @endforelse
        </ul>
    </section>

    <section class="footer-section">
        <div class="container footer">
            <div class="footer-grid">
                <div>
                    <h2>Contacts</h2>
                    <p>Restons en contact pour soutenir Conza ASBL et nos actions sur le terrain.</p>
                </div>
                <div class="contact-cards">
                    <div class="contact-card">
                        <span class="icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16.5 7.5a3.75 3.75 0 0 0-7.5 0c0 1.65 1.32 3.75 3.75 4.5.75.18 1.17.09 1.69-.06.29-.08.56-.18.94-.27.3-.08.56-.08.8.12l.54.54c-.62.37-1.38.69-2.19.69-2.93 0-5.25-2.36-5.25-5.25S9.57 4.5 12.5 4.5 17.75 6.86 17.75 9.75" />
                                <path d="M15.5 15.5c-.4.4-1.1.4-1.6.3-1.3-.2-2.7-1-3.5-1.8-.8-.8-1.6-2.1-1.8-3.5-.1-.5-.1-1.2.3-1.6.4-.4 1-.5 1.6-.5.4 0 .9.1 1.4.3.2.1.4.1.7.1.3 0 .6-.1.9-.1.2 0 .4 0 .6.1.3.1.7.2 1 .4.1.1.3.3.3.6.1.2.1.4 0 .6-.2.3-.4.6-.7.8z" />
                            </svg>
                        </span>
                        <div>
                            <strong>WhatsApp</strong>
                            <a href="https://wa.me/243812991950" target="_blank" rel="noopener">+243 812 991 950</a>
                        </div>
                    </div>
                    <div class="contact-card">
                        <span class="icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M15 3h-3a3 3 0 0 0-3 3v3H6v4h3v7h4v-7h3l1-4h-4V6a1 1 0 0 1 1-1h3V3z" />
                            </svg>
                        </span>
                        <div>
                            <strong>Facebook</strong>
                            <a href="https://facebook.com/conzaASBL" target="_blank" rel="noopener">Conza ASBL</a>
                        </div>
                    </div>
                    <div class="contact-card">
                        <span class="icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="5" />
                                <path d="M16 11.37a4 4 0 1 1-8 0 4 4 0 0 1 8 0z" />
                                <path d="M17.5 6.5h.01" />
                            </svg>
                        </span>
                        <div>
                            <strong>Instagram</strong>
                            <a href="https://instagram.com/conzaASBL" target="_blank" rel="noopener">Conza ASBL</a>
                        </div>
                    </div>
                    <div class="contact-card">
                        <span class="icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 7.5v9A2.5 2.5 0 0 0 5.5 19h13a2.5 2.5 0 0 0 2.5-2.5v-9A2.5 2.5 0 0 0 18.5 5h-13A2.5 2.5 0 0 0 3 7.5z" />
                                <path d="M3 7.5l9 6 9-6" />
                            </svg>
                        </span>
                        <div>
                            <strong>Email</strong>
                            <a href="mailto:conzaasbl243@gmail.com">conzaasbl243@gmail.com</a>
                        </div>
                    </div>
                    <div class="contact-card">
                        <span class="icon">
                            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false" width="24" height="24">
                                <rect width="24" height="24" rx="4" fill="#FF0000"></rect>
                                <polygon points="9.5,7.5 16.5,12 9.5,16.5" fill="#fff"></polygon>
                            </svg>
                        </span>
                        <div>
                            <strong>YouTube</strong>
                            <a href="https://www.youtube.com/@CONZA243tv" target="_blank" rel="noopener">@CONZA243tv</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
