@extends('layouts.app')

@section('title', 'Utilisateurs - Administration')

@section('content')
    <main class="container section">
        <div class="toolbar">
            <h2 class="admin-page-title">Utilisateurs</h2>
            <a href="{{ route('admin.index') }}" class="btn small secondary">Retour</a>
        </div>

        <div class="card admin-table-card">
            <div class="admin-table-wrap">
            <table class="admin-users-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $u)
                        <tr>
                            <td>{{ $u->id }}</td>
                            <td class="admin-user-name">{{ $u->name }}</td>
                            <td class="admin-user-email">{{ $u->email }}</td>
                            <td>{{ $u->role }}</td>
                            <td>{{ $u->status ?? 'active' }}</td>
                            <td class="admin-user-actions">
                                <div class="admin-action-row">
                                {{-- Role editor (only super-admin) --}}
                                @if(auth()->id() == $superAdminId)
                                    @if($u->id == $superAdminId)
                                        <strong>Super‑admin</strong>
                                    @else
                                        <form method="POST" action="{{ route('admin.users.updateRole', $u->id) }}" style="display:inline;">
                                            @csrf
                                            <select name="role" onchange="this.form.submit()" style="margin-right:8px;">
                                                <option value="member" {{ ($u->role ?? 'member') === 'member' ? 'selected' : '' }}>member</option>
                                                <option value="admin" {{ ($u->role ?? 'member') === 'admin' ? 'selected' : '' }}>admin</option>
                                            </select>
                                        </form>
                                    @endif
                                @endif

                                {{-- Block / Unblock (admins and super-admin) --}}
                                @if(auth()->user() && (auth()->id() == $superAdminId || auth()->user()->role === 'admin'))
                                    @if($u->id != $superAdminId)
                                        <form method="POST" action="{{ route('admin.users.toggleBlock', $u->id) }}" style="display:inline;margin-left:8px;">
                                            @csrf
                                            <button type="submit" class="btn small secondary" style="background:{{ ($u->status ?? 'active') === 'blocked' ? '#16a34a' : '#f59e0b' }};">
                                                {{ ($u->status ?? 'active') === 'blocked' ? 'Débloquer' : 'Bloquer' }}
                                            </button>
                                        </form>
                                    @endif
                                @endif

                                {{-- Delete (admins and super-admin with existing rules) --}}
                                @if(auth()->user() && (auth()->id() == $superAdminId || auth()->user()->role === 'admin'))
                                    @if($u->id != $superAdminId)
                                        <form method="POST" action="{{ route('admin.users.delete', $u->id) }}" style="display:inline;margin-left:8px;" onsubmit="return confirm('Supprimer cet utilisateur ?');">
                                            @csrf
                                            <button type="submit" class="btn small" style="background:#dc2626;">Supprimer</button>
                                        </form>
                                    @endif
                                @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>

            <div style="margin-top:12px;">{{ $users->links() }}</div>
        </div>
    </main>
@endsection
