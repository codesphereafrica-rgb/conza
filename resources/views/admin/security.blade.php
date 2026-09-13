@extends('layouts.app')

@section('title', 'Panneau de sécurité - Administration')

@section('content')
    <main class="container section">
        <div class="toolbar">
            <div>
                <h2 class="admin-page-title">Panneau de sécurité</h2>
                <p class="muted">Surveillance des visiteurs et des adresses IP.</p>
            </div>
            <a href="{{ route('admin.index') }}" class="btn small secondary">Retour</a>
        </div>

        <div class="admin-security-summary">
            <div>
                <strong>{{ $blockedCount }}</strong>
                <span>IP bloquée(s) actuellement</span>
            </div>
            <form method="POST" action="{{ route('admin.security.unblock-all') }}" onsubmit="return confirm('Débloquer toutes les IP actuellement bloquées ?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn small secondary" {{ $blockedCount === 0 ? 'disabled' : '' }}>Débloquer tout</button>
            </form>
        </div>

        <div class="card admin-table-card">
            <div class="admin-table-wrap">
                <table class="admin-security-table">
                    <thead>
                        <tr>
                            <th>IP</th>
                            <th>Pays / Ville</th>
                            <th>FAI</th>
                            <th>Device / Modèle</th>
                            <th>Page</th>
                            <th>Connecté ?</th>
                            <th>Heure</th>
                            <th>Temps sur site</th>
                            <th>Tentatives échouées</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($visitorLogs as $visitor)
                            @php
                                $blocked = $blockedIps->get($visitor->ip);
                                $seconds = max(0, (int) $visitor->temps_passe);
                            @endphp
                            <tr>
                                <td><strong>{{ $visitor->ip }}</strong></td>
                                <td>{{ $visitor->country ?: '-' }}{{ $visitor->city ? ' / ' . $visitor->city : '' }}</td>
                                <td>{{ $visitor->isp ?: '-' }}</td>
                                <td>{{ $visitor->platform ?: '-' }}{{ $visitor->device_model ? ' / ' . $visitor->device_model : '' }}</td>
                                <td class="admin-security-page" title="{{ $visitor->page_visitee }}">{{ $visitor->page_visitee ?: '-' }}</td>
                                <td>{{ $visitor->is_connected ? 'Oui' : 'Non' }}</td>
                                <td>{{ $visitor->last_seen?->format('d/m/Y H:i:s') ?: '-' }}</td>
                                <td>{{ sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60) }}</td>
                                <td>{{ $visitor->nombre_tentatives_login }}</td>
                                <td>
                                    @if($blocked)
                                        <form method="POST" action="{{ route('admin.security.unblock', $blocked->id) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn small security-unblock-button">Débloquer maintenant</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.security.block', $visitor->id) }}">
                                            @csrf
                                            <button type="submit" class="btn small security-block-button">Bloquer 24h</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="muted">Aucune visite enregistrée.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <style>
        .admin-security-summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
            padding: 16px;
            border: 1px solid #bbf7d0;
            border-radius: 12px;
            background: #f0fdf4;
        }
        .admin-security-summary strong {
            display: block;
            color: #b91c1c;
            font-size: 1.7rem;
        }
        .admin-security-summary span { color: #166534; }
        .admin-security-table {
            width: 100%;
            min-width: 1250px;
            border-collapse: collapse;
        }
        .admin-security-table th {
            padding: 12px 10px;
            text-align: left;
            background: #f0fdf4;
            color: var(--primary-dark);
            border-bottom: 2px solid #bbf7d0;
            font-size: .82rem;
        }
        .admin-security-table td {
            padding: 11px 10px;
            border-bottom: 1px solid #ecfdf5;
            vertical-align: middle;
            font-size: .85rem;
        }
        .admin-security-table tbody tr:hover { background: #f8fafc; }
        .admin-security-page {
            max-width: 260px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .security-block-button { background: #dc2626; }
        .security-unblock-button { background: #16a34a; }
        @media (max-width: 640px) {
            .admin-security-summary { align-items: flex-start; flex-direction: column; }
        }
    </style>

    <script>
        window.setTimeout(function () { window.location.reload(); }, 10000);
    </script>
@endsection
