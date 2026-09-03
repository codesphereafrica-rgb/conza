@extends('layouts.app')

@section('title', 'Utilisateurs - Administration')

@section('content')
    <main class="container section">
        <div class="toolbar">
            <h2>Utilisateurs</h2>
            <a href="{{ route('admin.index') }}" class="btn small secondary">Retour</a>
        </div>

        <div class="card">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="text-align:left;padding:6px;border-bottom:1px solid #eee;">ID</th>
                        <th style="text-align:left;padding:6px;border-bottom:1px solid #eee;">Nom</th>
                        <th style="text-align:left;padding:6px;border-bottom:1px solid #eee;">Email</th>
                        <th style="text-align:left;padding:6px;border-bottom:1px solid #eee;">Rôle</th>
                        <th style="padding:6px;border-bottom:1px solid #eee;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $u)
                        <tr>
                            <td style="padding:6px;border-bottom:1px solid #f6f6f6;">{{ $u->id }}</td>
                            <td style="padding:6px;border-bottom:1px solid #f6f6f6;">{{ $u->name }}</td>
                            <td style="padding:6px;border-bottom:1px solid #f6f6f6;">{{ $u->email }}</td>
                            <td style="padding:6px;border-bottom:1px solid #f6f6f6;">{{ $u->role }}</td>
                            <td style="padding:6px;border-bottom:1px solid #f6f6f6;">{{ $u->status ?? 'active' }}</td>
                            <td style="padding:6px;border-bottom:1px solid #f6f6f6;">
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
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="margin-top:12px;">{{ $users->links() }}</div>
        </div>
    </main>
@endsection
