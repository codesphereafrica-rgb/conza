<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $adsenseClientId = env('ADSENSE_CLIENT_ID', 'ca-pub-3688942362866671');
        $adsenseAdSlot = env('ADSENSE_AD_SLOT');
    @endphp
    @if(env('APP_ENV') !== 'local')
        <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={{ $adsenseClientId }}" crossorigin="anonymous"></script>
    @endif
    <title>@yield('title', 'ASBL Forum')</title>
    <style>
        :root {
            --bg: #f4f7fb;
            --card: #ffffff;
            --primary: #0f766e;
            --primary-dark: #115e59;
            --text: #1f2937;
            --muted: #6b7280;
            --border: #e5e7eb;
            --success: #16a34a;
            --warning: #f59e0b;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
        }
        a { color: inherit; text-decoration: none; }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .topbar {
            background: var(--primary-dark);
            color: white;
            padding: 16px 0;
        }
        .topbar-inner {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .menu-toggle { border: 0; background: transparent; color: white; font-size: 1.6rem; cursor: pointer; padding: 4px; }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-size: 1.3rem;
            font-weight: 700;
        }
        .brand img {
            height: 40px;
            width: auto;
            display: block;
        }
        .nav {
            display: none;
            position: absolute;
            top: calc(100% + 10px);
            left: 20px;
            z-index: 20;
            min-width: 230px;
            padding: 10px;
            background: var(--primary-dark);
            border: 1px solid rgba(255,255,255,.18);
            border-radius: 12px;
            box-shadow: 0 16px 30px rgba(15, 23, 42, .2);
            flex-direction: column;
            gap: 14px;
            align-items: center;
            margin-left: 0;
        }
        .nav.is-open { display: flex; }
        .topbar-inner { position: relative; }
        .header-actions { display: flex; align-items: center; gap: 10px; margin-left: auto; }
        .nav a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 14px;
            border-radius: 999px;
            background: rgba(255,255,255,0.12);
            color: white;
            font-weight: 700;
            transition: background 0.2s ease, transform 0.2s ease;
        }
        .profile-avatar { display: inline-flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 50%; object-fit: cover; background: #fbbf24; color: #422006; font-weight: 800; }
        .avatar-only { margin-left: 2px; }
        .profile-avatar-large { width: 76px; height: 76px; font-size: 1.8rem; }
        .profile-heading { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; }
        .locked-input { background: #f3f4f6; color: #6b7280; cursor: not-allowed; }
        .form-link { display: inline-block; margin-left: 12px; color: var(--primary-dark); font-weight: 700; }
        .eyebrow { color: var(--primary-dark); font-size: .78rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
        .auth-panel { max-width: 560px; }
        .nav a:hover,
        .nav a:focus {
            background: rgba(255,255,255,0.22);
            transform: translateY(-1px);
        }
        .nav a.active {
            background: rgba(255,255,255,0.28);
        }
        .btn {
            display: inline-block;
            border: none;
            border-radius: 10px;
            padding: 10px 16px;
            background: var(--primary);
            color: white;
            cursor: pointer;
            font-weight: 600;
        }
        .btn.secondary {
            background: #e5e7eb;
            color: var(--text);
        }
        .btn.small {
            padding: 8px 12px;
            font-size: 0.9rem;
        }
        .hero {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, rgba(15, 118, 110, 0.84), rgba(17, 94, 89, 0.88)), url("{{ url('/images_conza/ancetres_congolais.jpeg') }}") center/cover no-repeat;
            color: white;
            padding: 60px 0 60px;
        }
        .hero::after {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at top right, rgba(255,255,255,0.08), transparent 36%);
            pointer-events: none;
            z-index: 1;
        }
        .hero-right-visuals {
            position: relative;
            width: min(440px, 42%);
            min-height: 300px;
            border-radius: 28px;
            overflow: hidden;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.22);
            box-shadow: 0 20px 60px rgba(15, 23, 42, 0.18);
            flex: 0 0 440px;
            margin-left: 44px;
        }
        .hero-visual {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0;
            transform: scale(1.04);
            filter: saturate(1.1);
            z-index: 1;
            transition: opacity 1.2s ease, transform 1.2s ease;
        }
        .hero-visual.is-active {
            opacity: 1;
            transform: scale(1);
            z-index: 2;
        }
        .hero-visual-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(15,118,110,0.04), rgba(15,118,110,0.18));
            z-index: 99;
            pointer-events: none;
        }
        .hero-content {
            position: relative;
            z-index: 2;
            opacity: 0;
            transform: translateY(24px);
            animation: fadeInUp 1s ease forwards;
            max-width: 1120px;
            margin: 0 auto;
            padding: 0 16px;
        }
        .hero-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 50px;
            flex-wrap: wrap;
        }
        @media (min-width: 1120px) {
            .hero-top {
                flex-wrap: nowrap;
            }
        }
        .hero-text-column {
            flex: 1 1 calc(100% - 500px);
            min-width: 300px;
            text-align: left;
            max-width: calc(100% - 500px);
        }
        .hero-text-column h1 {
            margin-bottom: 18px;
            font-size: clamp(2.2rem, 3.5vw, 3.2rem);
            line-height: 1.05;
            max-width: 620px;
        }
        .hero-text-column p {
            max-width: 560px;
            margin: 0;
            line-height: 1.75;
            color: rgba(255,255,255,0.92);
        }
        .objective-list {
            margin: 0;
            padding-left: 1.1rem;
            line-height: 1.8;
            list-style: disc;
        }
        .objective-list li + li {
            margin-top: 8px;
        }
        .hero-actions {
            margin-top: 24px;
            display: flex;
            justify-content: flex-start;
            gap: 14px;
            flex-wrap: wrap;
        }
        .ad-slot { min-height: 120px; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .footer-section {
            width: 100%;
            background: #0f766e;
            padding: 40px 0 60px;
        }
        .footer {
            color: white;
        }
        .footer a { color: #a7f3d0; }
        .footer-grid {
            display: grid;
            gap: 32px;
            align-items: start;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        }
        .footer-grid h2 { margin-top: 0; }
        .footer-grid p {
            max-width: 520px;
            line-height: 1.7;
            color: rgba(255,255,255,0.86);
        }
        .contact-cards {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }
        .contact-card {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 18px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.14);
            border-radius: 18px;
            box-shadow: 0 24px 50px rgba(15, 23, 42, 0.12);
            flex: 1 1 220px;
            min-width: 220px;
            max-width: 260px;
            min-height: 96px;
        }
        .contact-cards { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .contact-card .icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            min-width: 38px;
            height: 38px;
            border-radius: 12px;
            background: rgba(255,255,255,0.16);
            color: #b5f3dd;
        }
        .contact-card .icon svg {
            width: 22px;
            height: 22px;
        }
        .contact-card strong {
            display: block;
            margin-bottom: 6px;
            font-size: 1rem;
        }
        .contact-card a {
            color: #d9f7ee;
        }
        .card-with-media {
            display: flex;
            flex-direction: column;
            gap: 18px;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.9s ease forwards;
        }
        .card-with-media:nth-child(1) { animation-delay: 0.15s; }
        .card-with-media:nth-child(2) { animation-delay: 0.3s; }
        .card-with-media:nth-child(3) { animation-delay: 0.45s; }
        .card-with-media img {
            width: 100%;
            height: auto;
            border-radius: 18px;
            object-fit: cover;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.15);
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(24px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .hero h1 {
            margin-top: 0;
        }
        .footer a { color: #a7f3d0; }
        .footer-grid { display: grid; gap: 20px; }
        .footer-grid h2 { margin-top: 0; }
        .card-with-media { display: flex; flex-direction: column; gap: 18px; }
        .card-with-media img {
            width: 100%;
            height: auto;
            border-radius: 18px;
            object-fit: cover;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.15);
        }
        .hero h1 {
            margin-top: 0;
        }
        .hero h1 {
            margin: 0 0 20px;
            font-size: clamp(2rem, 5vw, 3.5rem);
        }
        .hero p {
            font-size: 1.1rem;
            max-width: 700px;
            line-height: 1.7;
        }
        .section {
            padding: 40px 0;
        }
        .grid {
            display: grid;
            gap: 24px;
        }
        .grid-3 { grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); }
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        }
        .muted { color: var(--muted); }
        .stats {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .badge {
            display: inline-block;
            background: rgba(15, 118, 110, 0.1);
            color: var(--primary-dark);
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 700;
        }
        .list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            gap: 16px;
        }
        .list-item {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px;
            background: white;
        }
        .form-group {
            margin-bottom: 16px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        input, textarea, select {
            width: 100%;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: white;
        }
        textarea { min-height: 140px; resize: vertical; }
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid transparent;
        }
        .alert-success { background: #ecfdf5; color: #166534; border-color: #bbf7d0; }
        .alert-error { background: #fef2f2; color: #991b1b; border-color: #fecaca; }
        .alert-info { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .search-box {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .search-box input {
            width: 260px;
        }
        .progress-wrap {
            width: 100%;
            background: #e5e7eb;
            height: 16px;
            border-radius: 999px;
            overflow: hidden;
            margin-top: 10px;
        }
        .progress-bar {
            background: linear-gradient(90deg, #10b981, #0f766e);
            height: 100%;
        }
        @media (max-width: 768px) {
            .topbar-inner {
                flex-wrap: wrap;
            }
            .menu-toggle { display: inline-flex; order: 0; }
            .brand { order: 1; }
            .header-actions { order: 2; }
            .nav { top: calc(100% + 8px); left: 12px; right: 12px; min-width: 0; align-items: stretch; }
            .nav a, .nav form { width: 100%; }
            .nav a { border-radius: 8px; justify-content: flex-start; }
            .nav form .btn { width: 100%; text-align: left; }
            .profile-heading { align-items: flex-start; }
            .hero-top { justify-content: center; text-align: center; }
            .hero-text-column { max-width: 100%; min-width: 0; text-align: center; }
            .hero-actions { justify-content: center; }
            .hero-right-visuals { width: 100%; max-width: 440px; flex-basis: auto; margin-left: 0; }
            .contact-cards { gap: 8px; }
            .contact-card { min-width: 0; max-width: none; padding: 10px; gap: 8px; border-radius: 10px; }
            .contact-card .icon { width: 30px; min-width: 30px; height: 30px; border-radius: 8px; }
            .contact-card .icon svg { width: 17px; height: 17px; }
            .contact-card strong { font-size: .82rem; margin-bottom: 3px; }
            .contact-card a { font-size: .74rem; overflow-wrap: anywhere; }
            .nav {
                width: 100%;
            }
            .search-box input {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="container topbar-inner">
            <button class="menu-toggle" type="button" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="main-nav">☰</button>
            <a href="{{ route('home') }}" class="brand" aria-label="Conza ASBL Forum">
                <img src="{{ url('/images_conza/logo_conza_v3.png') }}" alt="Logo Conza ASBL">
                <span>Forum</span>
            </a>
            <nav class="nav" id="main-nav">
                <a href="{{ route('home') }}">Accueil</a>
                <a href="{{ route('forum.index') }}">Forum</a>
                <a href="{{ route('donations.index') }}">Dons</a>
                @auth
                    <a href="{{ route('profile.edit') }}">Profil</a>
                @else
                    <a href="{{ route('profile.edit') }}">Profil</a>
                @endauth
                @auth
                    @php
                        $superAdminId = \App\Models\Setting::get('super_admin_id');
                        $isAdmin = (auth()->id() == $superAdminId) || (auth()->user()->role ?? null) === 'admin';
                    @endphp
                    @if($isAdmin)
                        <a href="{{ route('forum.create-topic') }}">Nouveau sujet</a>
                        @if($isAdmin)
                            <a href="{{ route('admin.index') }}">Admin</a>
                        @endif
                    @endif
                    <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="btn small secondary">Déconnexion</button>
                    </form>
                @else
                    <a href="{{ route('login') }}">Se connecter</a>
                    <a href="{{ route('register') }}">Inscription</a>
                @endauth
            </nav>
            <div class="header-actions">
                @auth
                    <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="btn small secondary">Déconnexion</button>
                    </form>
                    <a href="{{ route('profile.edit') }}" class="avatar-only" aria-label="Ouvrir le profil">
                        @if(auth()->user()->avatar)
                            <img class="profile-avatar" src="{{ asset('storage/' . auth()->user()->avatar) }}" alt="Avatar">
                        @else
                            <span class="profile-avatar" aria-hidden="true">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        @endif
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn small secondary">Connexion</a>
                    <a href="{{ route('register') }}" class="btn small">Inscription</a>
                @endauth
            </div>
        </div>
    </header>

    @if (session('success'))
        <div class="container section" style="padding-bottom:0;">
            <div class="alert alert-success">{{ session('success') }}</div>
        </div>
    @endif

    @if (session('info'))
        <div class="container section" style="padding-bottom:0;">
            <div class="alert alert-info">{{ session('info') }}</div>
        </div>
    @endif

    {{-- Toast container for session messages --}}
    <div id="toast-container" aria-live="polite" aria-atomic="true" style="position:fixed;top:20px;right:20px;z-index:9999;">
    </div>

    <script>
        (function(){
            const container = document.getElementById('toast-container');
            function makeToast(message, type='info'){
                if(!message) return;
                const toast = document.createElement('div');
                toast.className = 'card';
                toast.style.minWidth = '260px';
                toast.style.marginBottom = '10px';
                toast.style.boxShadow = '0 8px 30px rgba(15,23,42,0.12)';
                toast.style.padding = '12px 14px';
                toast.style.borderLeft = type === 'error' ? '4px solid #dc2626' : (type === 'success' ? '4px solid #16a34a' : '4px solid #0f766e');
                toast.innerHTML = '<div style="font-weight:700;margin-bottom:6px;">' + (type === 'error' ? 'Erreur' : (type === 'success' ? 'Succès' : 'Info')) + '</div><div style="font-size:0.95rem;">'+message+'</div>';
                container.appendChild(toast);
                setTimeout(function(){ toast.style.opacity = '0'; toast.style.transition = 'opacity 0.4s ease'; setTimeout(()=>toast.remove(),400); }, 5000);
            }

            // Server-provided flash messages
            @if(session('success'))
                makeToast(@json(session('success')), 'success');
            @endif
            @if(session('error'))
                makeToast(@json(session('error')), 'error');
            @endif
            @if(session('info'))
                makeToast(@json(session('info')), 'info');
            @endif
        })();
    </script>

    @if(env('APP_ENV') !== 'local' && $adsenseAdSlot)
        <div class="container section" style="padding-top: 12px; padding-bottom: 0;">
            <div class="ad-slot">
                <ins class="adsbygoogle"
                    style="display:block; width:100%;"
                    data-ad-client="{{ $adsenseClientId }}"
                    data-ad-slot="{{ $adsenseAdSlot }}"
                    data-ad-format="auto"
                    data-full-width-responsive="true"></ins>
                <script>
                    (adsbygoogle = window.adsbygoogle || []).push({});
                </script>
            </div>
        </div>
    @endif

    <script>
        const menuButton = document.querySelector('.menu-toggle');
        const mainNav = document.querySelector('#main-nav');
        menuButton?.addEventListener('click', () => {
            const open = mainNav.classList.toggle('is-open');
            menuButton.setAttribute('aria-expanded', String(open));
        });
    </script>

    @yield('content')
</body>
</html>
