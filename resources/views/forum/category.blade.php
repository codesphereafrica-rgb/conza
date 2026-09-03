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
                <li class="list-item">
                    <h3><a href="{{ route('forum.topic', $topic->id) }}">{{ $topic->title }}</a></h3>
                    <p class="muted">Par {{ $topic->user->name }} · {{ $topic->created_at->diffForHumans() }}</p>
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
