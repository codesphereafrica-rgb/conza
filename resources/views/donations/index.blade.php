@extends('layouts.app')

@section('title', 'Dons - ASBL Forum')

@section('content')
    <main class="container section" style="max-width: 860px;">
        <div class="card">
            <div class="badge">Soutenez l'ASBL</div>
            <h2 style="margin-top: 12px;">Aidez-nous à financer nos projets</h2>
            <p class="muted">
                Vos contributions permettent de financer des actions éducatives, sociales et environnementales
                menées par l'association.
            </p>

            <div style="margin-top: 20px;">
                <div class="ad-banner" style="margin-bottom: 20px;">
                    <img src="{{ url('/images_conza/Chutes_de_la_Lofoï.jpg') }}" alt="Bannière donation Conza">
                    <div class="ad-banner-copy">
                        <span class="badge">Paiement sécurisé</span>
                        <h3>Contribuez à la mission Conza</h3>
                        <p>Vos dons sont utilisés pour soutenir les projets communautaires, la communication et les actions de terrain.</p>
                    </div>
                </div>

                <strong>Objectif : @if($target) {{ number_format($target, 0, ',', ' ') }} € @else Non défini @endif</strong>
                @auth
                    @if((auth()->user()->role ?? null) === 'admin')
                        <div class="muted" style="margin-top: 8px;">Déjà collecté : {{ number_format($collected, 0, ',', ' ') }} €</div>
                    @endif
                @endauth
                <div class="progress-wrap">
                    <div class="progress-bar" style="width: {{ $target ? $progress : 0 }}%;"></div>
                </div>
                <p class="muted" style="margin-top: 10px;">Progression : {{ $target ? number_format($progress, 1, ',', ' ') . '%' : 'N/A' }}</p>

                @auth
                    @if(($userPendingDonations ?? collect())->count())
                        <div style="margin-top:18px; border:1px solid #f0f0f0; border-radius:10px; padding:12px; background:#fafafa;">
                            <h4 style="margin:0 0 8px;">Paiement en attente</h4>
                            @foreach($userPendingDonations as $pendingDonation)
                                <div style="margin-bottom:14px;">
                                    <div style="display:flex; justify-content:space-between; gap:12px; font-size:13px; margin-bottom:6px;">
                                        <span>{{ number_format($pendingDonation->amount, 2, ',', ' ') }} €</span>
                                        <span>Validation dans :</span>
                                    </div>
                                    <div class="progress-wrap" style="height:10px;">
                                        <div class="progress-bar pending-user-timer-bar" data-created-at="{{ $pendingDonation->created_at->toIso8601String() }}" style="width:0%;"></div>
                                    </div>
                                    <div class="pending-user-timer-text" style="margin-top:6px; font-size:12px; color:#374151;">00:00</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endauth

                @if($labyrintheEnabled)
                    <div style="margin-top: 18px; padding: 14px 16px; border-radius: 12px; background: #ecfdf5; border: 1px solid #bbf7d0; color: #065f46;">
                        <strong>Paiement Labyrinthe activé</strong>
                        <div style="margin-top: 6px; font-size: 0.95rem;">Le don sera traité via l’API Labyrinthe avec redirection vers le paiement sécurisé.</div>
                    </div>
                @else
                    <div style="margin-top: 18px; padding: 14px 16px; border-radius: 12px; background: #fff7ed; border: 1px solid #fed7aa; color: #9a4d00;">
                        <strong>Paiement Labyrinthe en attente de configuration</strong>
                        <div style="margin-top: 6px; font-size: 0.95rem;">Ajoute tes clés API Labyrinthe dans le fichier .env pour activer le paiement direct.</div>
                    </div>
                @endif

                @auth
                    @if((auth()->user()->role ?? null) === 'admin')
                        <div style="margin-top:12px;">
                            <h4>Résumé des contributions</h4>
                            <ul style="margin:0;padding-left:16px;">
                                @if(!empty($byStatus) && $byStatus->count())
                                    @foreach($byStatus as $s)
                                        <li>{{ ucfirst($s->status) }} : {{ number_format($s->total ?? 0, 2, ',', ' ') }} € ({{ $s->count }})</li>
                                    @endforeach
                                @else
                                    <li class="muted">Aucun don enregistré.</li>
                                @endif
                            </ul>
                        </div>

                        <div style="margin-top:16px;">
                            <h4>Dons récents</h4>
                            @if($recentDonations && $recentDonations->count())
                                <table style="width:100%;border-collapse:collapse;margin-top:8px;">
                                    <thead>
                                        <tr>
                                            <th style="text-align:left;padding:6px;border-bottom:1px solid #eee;">Date</th>
                                            <th style="text-align:left;padding:6px;border-bottom:1px solid #eee;">Donateur</th>
                                            <th style="text-align:right;padding:6px;border-bottom:1px solid #eee;">Montant</th>
                                            <th style="text-align:left;padding:6px;border-bottom:1px solid #eee;">Statut</th>
                                            <th style="padding:6px;border-bottom:1px solid #eee;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recentDonations as $d)
                                            <tr>
                                                <td style="padding:6px;border-bottom:1px solid #f6f6f6;">{{ $d->created_at->format('Y-m-d H:i') }}</td>
                                                <td style="padding:6px;border-bottom:1px solid #f6f6f6;">{{ $d->user?->name ?? 'Invité' }}</td>
                                                <td style="padding:6px;text-align:right;border-bottom:1px solid #f6f6f6;">{{ number_format($d->amount, 2, ',', ' ') }} €</td>
                                                <td style="padding:6px;border-bottom:1px solid #f6f6f6;">{{ ucfirst($d->status) }}</td>
                                                <td style="padding:6px;border-bottom:1px solid #f6f6f6;">
                                                    @if($d->status !== 'paid')
                                                        <form method="POST" action="{{ route('admin.donations.markPaid', $d->id) }}" onsubmit="return confirm('Marquer ce don comme payé ?');">
                                                            @csrf
                                                            <button type="submit" class="btn small">Marquer payé</button>
                                                        </form>
                                                    @else
                                                        <form method="POST" action="{{ route('admin.donations.markPending', $d->id) }}" onsubmit="return confirm('Remettre ce don en pending ?');">
                                                            @csrf
                                                            <button type="submit" class="btn small secondary">Annuler payé</button>
                                                        </form>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <p class="muted">Aucun don récent.</p>
                            @endif
                        </div>
                    @endif
                @endauth
            </div>
        </div>

        @auth
            <div class="card" style="margin-top: 24px;">
                <h3>Faire un don</h3>
                <form method="POST" action="{{ route('donations.store') }}">
                    @csrf

                    <div class="form-group">
                        <label for="phone">Numéro de téléphone</label>
                        <input id="phone" name="phone" type="tel" placeholder="0970000000" required>
                    </div>

                    <div class="form-group">
                        <label for="amount">Montant</label>
                        <input id="amount" name="amount" type="number" min="1" step="1" placeholder="1000" required>
                    </div>

                    <div class="form-group">
                        <label for="currency">Devise</label>
                        <select id="currency" name="currency" required>
                            <option value="CDF">CDF</option>
                            <option value="USD">USD</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="country">Pays</label>
                        <select id="country" name="country" required>
                            <option value="CD">RD Congo</option>
                        </select>
                    </div>

                    <button type="submit" class="btn">Valider le don</button>
                </form>
            </div>
        @else
            <div class="card" style="margin-top: 24px;">
                <p class="muted">Connectez-vous pour effectuer un don.</p>
            </div>
        @endauth
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const bars = document.querySelectorAll('.pending-user-timer-bar');
            bars.forEach(function (bar) {
                const createdAt = new Date(bar.dataset.createdAt).getTime();
                const durationMs = 1 * 60 * 1000;

                function update() {
                    const now = Date.now();
                    const elapsed = Math.max(0, now - createdAt);
                    const remaining = Math.max(0, durationMs - elapsed);
                    const ratio = Math.min(100, (elapsed / durationMs) * 100);

                    bar.style.width = ratio + '%';
                    const text = bar.parentElement.parentElement.querySelector('.pending-user-timer-text');
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
