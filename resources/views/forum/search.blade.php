@extends('layouts.app')

@section('title', 'Recherche - ASBL Forum')

@section('content')
    <main class="container section">
        <div class="toolbar">
            <h2>Recherche</h2>
            <form class="search-box" method="GET" action="{{ route('forum.search') }}">
                <input type="text" name="q" placeholder="Rechercher" value="{{ $query ?? '' }}">
                <button type="submit" class="btn small">Chercher</button>
            </form>
        </div>

        <ul class="list">
            @forelse($results as $topic)
                <li class="list-item">
                    <h3><a href="{{ route('forum.topic', $topic->id) }}">{{ $topic->title }}</a></h3>
                    <p class="muted">{{ $topic->category?->name ?? 'Sans catégorie' }} · {{ $topic->user->name }}</p>
                </li>
            @empty
                <li class="list-item">
                    <p class="muted">Aucun résultat trouvé pour votre recherche.</p>
                </li>
            @endforelse
        </ul>

        {{ $results->links() }}
    </main>
@endsection
