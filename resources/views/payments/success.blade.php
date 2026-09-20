@extends('layouts.app')

@section('title', 'Paiement confirmé')

@section('content')
    <main class="container section">
        <div class="card">
            <h2>{{ $paid ? 'Paiement confirmé' : 'Paiement en attente de confirmation' }}</h2>
            <p class="muted">{{ $paid ? 'Merci pour votre contribution.' : 'Le statut du paiement n’est pas encore confirmé.' }}</p>
            <a class="btn" href="{{ route('donations.index') }}">Retour aux dons</a>
        </div>
    </main>
@endsection