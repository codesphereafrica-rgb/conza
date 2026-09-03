@extends('layouts.app')

@section('title', 'Inscription - ASBL Forum')

@section('content')
    <main class="container section" style="max-width: 560px;">
        <div class="card">
            <h2>Créer un compte</h2>

            <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data">
                @csrf

                <div class="form-group">
                    <label for="name">Nom ou pseudonyme</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required>
                    @error('name')
                        <div class="alert alert-error" style="margin-top: 8px;">{{ $message }}</div>
                    @enderror
                </div>

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

                <div class="form-group">
                    <label for="password_confirmation">Confirmation du mot de passe</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required>
                </div>

                <div class="form-group">
                    <label for="avatar">Photo de profil (facultative)</label>
                    <input id="avatar" name="avatar" type="file" accept="image/*">
                    @error('avatar')
                        <div class="alert alert-error" style="margin-top: 8px;">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn">S'inscrire</button>
            </form>
        </div>
    </main>
@endsection
