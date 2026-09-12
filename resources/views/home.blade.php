@extends('layouts.app')

@section('title', 'Accueil - Programme Conza')

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
                <div class="hero-right-visuals">
                    <div class="hero-carousel">
                    @php
                        $homeSlides = [
                            'ancetres_congolais.jpeg',
                            'ancetre_2.jpg',
                            'ngaliema_et_stanley.jpeg',
                            'kasa_vubu_et_moise_tshombe.png',
                            'Henry_Morton_Stanley.jpg',
                            'kasa_vubu.jpeg',
                            'lumumba_1.png',
                            'mobutu_1.jpeg',
                            'mobutu_2.jpeg',
                            'laurent_desire_kabila.jpg',
                            'camp_militaire.jpeg',
                            'Étienne_Tshisekedi.jpg',
                            'echangeur_1.jpeg',
                            'echangeur_2.png',
                            'sozacom.jpg',
                            'Immeuble_Sozacom_en_2024.jpg',
                            'statut_fikin.jpeg',
                            'plage_moanda_2.jpeg',
                            'plage_moanda_1.jpeg',
                            'Inga04.jpg',
                            'Ballet_Bana_Mampala-Africa_Museum_(1)_01.jpeg',
                            'Chutes_de_la_Lofoï.jpg',
                            'Chutes_Wagenia.jpg',
                            'dam.jpg',
                            'Garamba_National_Park_overhead.jpg',
                            'Aerial_view_of_the_Congo_River_near_Kisangani.jpg',
                            'gorille.jpg',
                            'Nyamulagira_volcano_(20439939664).jpg',
                            'Paysage_de_Nsele.jpg',
                            'ruzizi.jpeg',
                            'ruzizi2.jpeg',
                            'fleuve_congo.jpg',
                            'Inga_2006-projet.svg.png',
                            'itombwe_carte.jpg',
                            'itombwe-reserve-e.png',
                            'Lake_Bangweulu.jpg',
                        ];
                    @endphp
                    @foreach($homeSlides as $index => $slide)
                        <img
                            class="hero-visual{{ $index === 0 ? ' is-active' : '' }}"
                            src="{{ url('/images_conza/' . $slide) }}"
                            alt=""
                            aria-hidden="true"
                        >
                    @endforeach
                    <div class="hero-visual-overlay"></div>
                    </div>
                    <ul class="hero-values">
                        <li>S'assumer et assumer, la liberté ;</li>
                        <li>Citoyenneté optimale ;</li>
                        <li>Dignité authentique.</li>
                    </ul>
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
                @php
                    $primaryPost = $topic->posts()->first();
                    $likeCount = $primaryPost ? $primaryPost->reactions()->where('type', 'like')->count() : 0;
                    $commentCount = max(0, $topic->posts()->count() - 1);
                    $thumb = null;
                    if(!empty($topic->attachments) && is_array($topic->attachments)){
                        foreach($topic->attachments as $att){
                            $fp = is_array($att) ? ($att['url'] ?? null) : (is_string($att) ? str_replace('\\', '/', $att) : null);
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

                <li class="list-item" style="padding:12px;">
                    <div class="badge">{{ $topic->category->name ?? 'Forum' }}</div>
                    <h3 style="margin: 10px 0 10px; font-size: 1rem; line-height:1.4;">
                        <a href="{{ route('forum.topic', $topic->id) }}" style="text-decoration: underline;">{{ $topic->title }}</a>
                    </h3>

                    <div style="display:flex; align-items:flex-start; gap:12px; width:100%;">
                        @if($thumb)
                            <a href="{{ route('forum.topic', $topic->id) }}" style="flex-shrink:0;">
                                @if($thumb['type'] === 'image')
                                    <img src="{{ $thumb['url'] }}" alt="Image de la discussion" style="width:130px; min-width:130px; height:130px; object-fit:cover; border-radius:10px; border:1px solid #e5e7eb; background:#000; display:block;">
                                @else
                                    <img src="{{ $thumb['url'] }}" alt="Vidéo de la discussion" style="width:130px; min-width:130px; height:130px; object-fit:cover; border-radius:10px; border:1px solid #e5e7eb; background:#000; display:block;">
                                @endif
                            </a>
                        @endif

                        <div style="flex:1; min-width:0; display:flex; flex-direction:column;">
                            <p class="muted" style="margin:0 0 8px; font-size:0.9rem; line-height:1.5;">{{ Str::limit($topic->content ?: 'Aucune description disponible.', 160) }}</p>

                            <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:0;">
                                @if($primaryPost)
                                    <form method="POST" action="{{ route('forum.react', $primaryPost->id) }}">
                                        @csrf
                                        <input type="hidden" name="type" value="like">
                                        <button type="submit" class="btn small secondary" style="padding:7px 10px; font-size:0.78rem;">👍 Like <span>({{ $likeCount }})</span></button>
                                    </form>
                                @endif
                                <button type="button" class="btn small secondary share-btn" style="padding:7px 10px; font-size:0.78rem;" data-share-url="{{ route('forum.topic', $topic->id) }}" data-share-title="{{ $topic->title }}">Partager</button>
                            </div>
                        </div>
                    </div>
                </li>
            @empty
                <li class="list-item">
                    <p class="muted">Aucune discussion pour le moment.</p>
                </li>
            @endforelse
        </ul>
    </section>

@endsection
