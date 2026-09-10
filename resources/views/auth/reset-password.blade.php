@extends('layouts.app')

@section('title', 'Nouveau mot de passe - Conza')

@section('content')
    <main class="container section auth-panel">
        <div class="card">
            <span class="eyebrow">Sécurité du compte</span>
            <h2>Choisir un nouveau mot de passe</h2>
            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div class="form-group">
                    <label for="email">Adresse e-mail</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $email) }}" readonly required style="background-color:#e5e7eb; color:#6b7280; cursor:not-allowed;">
                </div>
                <div class="form-group">
                    <label for="password">Nouveau mot de passe</label>
                    <input id="password" name="password" type="password" required>
                    <small class="muted">Le mot de passe doit contenir au minimum 8 caracteres.</small>
                    @error('password')
                        <div class="alert alert-error" style="margin-top:8px; color:#dc2626;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="password_confirmation">Confirmation</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required>
                </div>
                <button type="submit" class="btn">Réinitialiser</button>
            </form>
        </div>
    </main>
@endsection