@extends('layouts.app')

@section('title', 'Archives des dons - Administration')

@section('content')
    <main class="container section">
        <div class="toolbar">
            <h2>Archives des dons</h2>
            <a href="{{ route('admin.index') }}" class="btn small secondary">Retour</a>
        </div>

        <div class="card">
            @if($archives->count())
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th style="text-align:left;padding:6px;border-bottom:1px solid #eee;">ID</th>
                            <th style="text-align:left;padding:6px;border-bottom:1px solid #eee;">Date</th>
                            <th style="text-align:left;padding:6px;border-bottom:1px solid #eee;">Donateur</th>
                            <th style="text-align:right;padding:6px;border-bottom:1px solid #eee;">Montant</th>
                            <th style="text-align:left;padding:6px;border-bottom:1px solid #eee;">Statut</th>
                            <th style="padding:6px;border-bottom:1px solid #eee;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($archives as $a)
                            <tr>
                                <td style="padding:6px;border-bottom:1px solid #f6f6f6;">{{ $a->original_id }}</td>
                                <td style="padding:6px;border-bottom:1px solid #f6f6f6;">{{ $a->donated_at ?? $a->created_at }}</td>
                                <td style="padding:6px;border-bottom:1px solid #f6f6f6;">{{ $a->user?->name ?? 'Invité' }}</td>
                                <td style="padding:6px;text-align:right;border-bottom:1px solid #f6f6f6;">{{ number_format($a->amount, 2, ',', ' ') }} €</td>
                                <td style="padding:6px;border-bottom:1px solid #f6f6f6;">{{ ucfirst($a->status) }}</td>
                                <td style="padding:6px;border-bottom:1px solid #f6f6f6;">
                                    <form method="POST" action="{{ route('admin.donations.restore', $a->id) }}" onsubmit="return confirm('Restaurer ce don depuis l\'archive ?');">
                                        @csrf
                                        <button type="submit" class="btn small">Restaurer</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div style="margin-top:12px;">{{ $archives->links() }}</div>
            @else
                <p class="muted">Aucune archive trouvée.</p>
            @endif
        </div>
    </main>
@endsection
