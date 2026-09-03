@extends('layouts.app')

@section('title', 'Administration - ASBL Forum')

@section('content')
    <main class="container section">
        <div class="toolbar">
            <h2>Administration</h2>
            <a href="{{ route('admin.categories') }}" class="btn small">Gérer les catégories</a>
            <a href="{{ route('admin.users') }}" class="btn small" style="margin-left:8px;">Gérer les utilisateurs</a>
        </div>

        <div class="grid grid-3">
            <div class="card">
                <h3>Utilisateurs</h3>
                <p class="muted">{{ $stats['users'] }} comptes</p>
            </div>
            <div class="card">
                <h3>Sujets</h3>
                <p class="muted">{{ $stats['topics'] }} discussions</p>
            </div>
            <div class="card">
                <h3>Catégories</h3>
                <p class="muted">{{ $stats['categories'] }} catégories</p>
            </div>
            <div class="card">
                <h3>Objectif de collecte</h3>
                <p class="muted">{{ $goal ? number_format((float)$goal, 2, ',', ' ') . ' €' : 'Non défini' }}</p>
                <form method="POST" action="{{ route('admin.goal.update') }}" style="margin-top:10px;">
                    @csrf
                    <label for="goal">Somme objectif (ex: 1500.00)</label>
                    <input id="goal" name="goal" type="text" value="{{ old('goal', $goal) }}" placeholder="0.00" style="width:100%;">
                    @error('goal')
                        <div class="alert alert-error" style="margin-top:8px;">{{ $message }}</div>
                    @enderror
                    <button type="submit" class="btn small" style="margin-top:8px;">Enregistrer</button>
                </form>
            </div>
            <div class="card">
                <h3>Montant payé</h3>
                <p class="muted">{{ number_format((float)($paidTotal ?? 0), 2, ',', ' ') }} €</p>
            </div>
            <div class="card">
                <h3>Pending</h3>
                <p class="muted">{{ number_format((float)($pendingTotal ?? 0), 2, ',', ' ') }} €</p>
                <small>{{ $pendingCount ?? 0 }} dons en attente</small>
                @if($pendingDonation)
                    <div style="margin-top:12px;">
                        <div style="font-size:12px; color:#6b7280; margin-bottom:6px;">Validation dans :</div>
                        <div class="progress-wrap" style="height:10px;">
                            <div class="progress-bar pending-timer-bar" data-created-at="{{ $pendingDonation->created_at->toIso8601String() }}" style="width: 0%; background:#f59e0b;"></div>
                        </div>
                        <div class="pending-timer-text" style="margin-top:8px; font-size:12px; color:#374151;">00:00</div>
                    </div>
                @endif
            </div>
            <div class="card">
                <h3>Réinitialisations</h3>
                <form method="POST" action="{{ route('admin.reset.goal') }}" onsubmit="return confirm('Réinitialiser l\'objectif ?');" style="margin-bottom:8px;">
                    @csrf
                    <button type="submit" class="btn small secondary">Réinitialiser l'objectif</button>
                </form>

                <form method="POST" action="{{ route('admin.reset.paid') }}" onsubmit="return confirm('Remettre tous les dons payés en pending (efface le total payé) ?');">
                    @csrf
                    <button type="submit" class="btn small" style="background:#dc2626;">Réinitialiser montants payés</button>
                </form>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const bars = document.querySelectorAll('.pending-timer-bar');
            bars.forEach(function (bar) {
                const createdAt = new Date(bar.dataset.createdAt).getTime();
                const durationMs = 1 * 60 * 1000;

                function update() {
                    const now = Date.now();
                    const elapsed = Math.max(0, now - createdAt);
                    const remaining = Math.max(0, durationMs - elapsed);
                    const ratio = Math.min(100, (elapsed / durationMs) * 100);

                    bar.style.width = ratio + '%';
                    const text = bar.parentElement.parentElement.querySelector('.pending-timer-text');
                    if (text) {
                        const totalSeconds = Math.ceil(remaining / 1000);
                        const minutes = String(Math.floor(totalSeconds / 60)).padStart(2, '0');
                        const seconds = String(totalSeconds % 60).padStart(2, '0');
                        text.textContent = minutes + ':' + seconds;
                    }
                }

                update();
                setInterval(update, 1000);
            });
        });
    </script>
@endsection
