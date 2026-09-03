@extends('layouts.app')

@section('title', 'Mon profil - Conza')

@section('content')
    <main class="container section auth-panel">
        <div class="card profile-card">
            <div class="profile-heading">
                @if($user->avatar)
                    <img class="profile-avatar profile-avatar-large" src="{{ $user->avatar }}" alt="Avatar de {{ $user->name }}">
                @else
                    <span class="profile-avatar profile-avatar-large" aria-hidden="true">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                @endif
                <div><span class="eyebrow">Espace personnel</span><h2>Mon profil</h2></div>
            </div>
            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label for="name">Nom</label>
                    <input id="name" type="text" value="{{ $user->name }}" disabled class="locked-input">
                </div>
                <div class="form-group">
                    <label for="email">Adresse e-mail</label>
                    <input id="email" type="email" value="{{ $user->email }}" disabled class="locked-input">
                </div>
                <div class="form-group">
                    <label for="avatar">Modifier uniquement l’avatar</label>
                    <input id="avatar" name="avatar" type="file" accept="image/*">
                    @error('avatar')<div class="alert alert-error" style="margin-top:8px;">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn">Enregistrer l’avatar</button>
            </form>
        </div>
    </main>
@endsection