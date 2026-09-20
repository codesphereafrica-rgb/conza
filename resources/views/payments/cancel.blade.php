@extends('layouts.app')

@section('title', 'Paiement annulé')

@section('content')
    <main class="container section"><div class="card"><h2>Paiement annulé</h2><p class="muted">La transaction a été annulée.</p><a class="btn" href="{{ route('donations.index') }}">Retour aux dons</a></div></main>
@endsection