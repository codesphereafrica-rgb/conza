@extends('layouts.app')

@section('title', 'Mot de passe oublié - Conza')

@section('content')
    <main class="container section auth-panel">
        <div class="card">
            <span class="eyebrow">Accès au compte</span>
            <h2>Réinitialiser le mot de passe</h2>
            <p class="muted">Indique ton adresse e-mail pour recevoir un lien sécurisé.</p>
            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="form-group">
                    <label for="email">Adresse e-mail</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
                    @error('email')<div class="alert alert-error" style="margin-top:8px;">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn">Envoyer le lien</button>
            </form>
        </div>
    </main>
@endsection