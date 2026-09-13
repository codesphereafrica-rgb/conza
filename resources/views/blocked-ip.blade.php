@extends('layouts.app')

@section('title', 'Accès temporairement bloqué')

@section('content')
    <main class="container section" style="max-width: 680px;">
        <div class="card" style="text-align:center;">
            <h1>Votre IP est bloquée temporairement</h1>
            <p class="muted">L'accès au site est temporairement indisponible pour cette adresse IP.</p>
        </div>
    </main>
@endsection
