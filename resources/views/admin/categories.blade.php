@extends('layouts.app')

@section('title', 'Catégories - Administration')

@section('content')
    <main class="container section" style="max-width: 760px;">
        <div class="toolbar">
            <h2 class="admin-page-title">Catégories</h2>
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
                    <li class="list-item admin-category-card">
                        <form id="category-update-{{ $category->id }}" method="POST" action="{{ route('admin.categories.update', $category->id) }}">
                            @csrf
                            @method('PUT')
                            <div class="form-group">
                                <label for="category-name-{{ $category->id }}">Nom</label>
                                <input id="category-name-{{ $category->id }}" name="name" type="text" value="{{ $category->name }}" required>
                            </div>
                            <div class="form-group">
                                <label for="category-description-{{ $category->id }}">Description</label>
                                <textarea id="category-description-{{ $category->id }}" name="description">{{ $category->description }}</textarea>
                            </div>
                        </form>
                        <div class="admin-action-row">
                            <button type="submit" form="category-update-{{ $category->id }}" class="btn small">Modifier</button>
                            <form method="POST" action="{{ route('admin.categories.destroy', $category->id) }}" onsubmit="return confirm('Supprimer cette catégorie ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn small" style="background:#dc2626;">Supprimer</button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </main>
@endsection
