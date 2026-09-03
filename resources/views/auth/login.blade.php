@extends('layouts.app')

@section('title', 'Connexion - ASBL Forum')

@section('content')
    <main class="container section" style="max-width: 560px;">
        <div class="card">
            <h2>Connexion</h2>

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="form-group">
                    <label for="email">Adresse e-mail</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required>
                    @error('email')
                        <div class="alert alert-error" style="margin-top: 8px;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input id="password" name="password" type="password" required>
                    @error('password')
                        <div class="alert alert-error" style="margin-top: 8px;">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn">Se connecter</button>
            </form>
        </div>
    </main>
@endsection
