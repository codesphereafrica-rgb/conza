@extends('layouts.app')

@section('title', 'Catégories - Administration')

@section('content')
    <main class="container section" style="max-width: 760px;">
        <div class="toolbar">
            <h2>Catégories</h2>
            <a href="{{ route('admin.index') }}" class="btn small secondary">Retour</a>
        </div>

        <div class="card">
            <h3>Ajouter une catégorie</h3>
            <form method="POST" action="{{ route('admin.categories.store') }}">
                @csrf

                <div class="form-group">
                    <label for="name">Nom</label>
                    <input id="name" name="name" type="text" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"></textarea>
                </div>

                <button type="submit" class="btn">Enregistrer</button>
            </form>
        </div>

        <div style="margin-top: 24px;">
            <ul class="list">
                @foreach($categories as $category)
                    <li class="list-item">
                        <h3>{{ $category->name }}</h3>
                        <p class="muted">{{ $category->description ?: 'Pas de description' }}</p>
                    </li>
                @endforeach
            </ul>
        </div>
    </main>
@endsection
