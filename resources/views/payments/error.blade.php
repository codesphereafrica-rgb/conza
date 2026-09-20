@extends('layouts.app')

@section('title', 'Paiement échoué')

@section('content')
    <main class="container section"><div class="card"><h2>Paiement échoué</h2><p class="muted">Votre paiement n’a pas pu être confirmé.</p><a class="btn" href="{{ route('donations.index') }}">Retour aux dons</a></div></main>
@endsection