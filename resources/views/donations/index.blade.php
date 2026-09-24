@extends('layouts.app')

@section('title', 'Dons - ASBL Forum')

@section('content')
    <style>
        .donation-form-card {
            overflow: hidden;
        }
        .donation-form-carousel {
            margin: -24px -24px 24px;
            padding: 22px 24px;
            background: #d1fae5;
            border-bottom: 1px solid #a7f3d0;
        }
        .donation-form-carousel h3 {
            margin: 0;
            color: #115e59;
            font-size: 28px;
            font-weight: 800;
        }
    </style>
    <main class="container section" style="max-width: 860px;">
        <div class="card">
            <div class="badge">Soutenez l'ASBL</div>
            <h2 style="margin-top: 12px;">Aidez-nous à financer nos projets</h2>
            <p class="muted">
                Vos contributions permettent de financer des actions éducatives, sociales et environnementales
                menées par l'association.
            </p>

            <div style="margin-top: 20px;">
                @if(filled(env('ADSENSE_CLIENT_ID')) && filled(env('ADSENSE_AD_SLOT')))
                    <div class="ad-slot donation-ad" style="margin-bottom: 20px;">
                        <ins class="adsbygoogle"
                            style="display:block; width:100%;"
                            data-ad-client="{{ env('ADSENSE_CLIENT_ID') }}"
                            data-ad-slot="{{ env('ADSENSE_AD_SLOT') }}"
                            data-ad-format="auto"
                            data-full-width-responsive="true"></ins>
                        <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
                    </div>
                @endif

                <strong>Objectif : @if($target) {{ number_format($target, 0, ',', ' ') }} $ @else Non défini @endif</strong>
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
                                @php($pendingCurrency = strtoupper($pendingDonation->currency ?? 'CDF'))
                                <div data-pending-donation data-order-id="{{ $pendingDonation->id }}" data-phone="{{ $pendingDonation->phone }}" data-provider="{{ strtoupper($pendingDonation->provider) }}" data-amount="{{ $pendingDonation->amount }}" style="margin-bottom:14px;">
                                    <div style="display:flex; justify-content:space-between; gap:12px; font-size:13px; margin-bottom:6px;">
                                        <span>{{ number_format($pendingDonation->amount, 2, ',', ' ') }} {{ $pendingCurrency }}</span>
                                        <span data-payment-status>En attente d'initialisation</span>
                                    </div>
                                    <button type="button" class="btn small" data-pawapay-pay>Payer avec Mobile Money</button>
                                    <div class="muted" data-payment-error style="display:none; margin-top:6px;"></div>
                                    <div class="progress-wrap" style="height:10px;">
                                        <div class="progress-bar pending-user-timer-bar" data-created-at="{{ $pendingDonation->created_at->toIso8601String() }}" data-reference="{{ $pendingDonation->external_reference }}" style="width:0%;"></div>
                                    </div>
                                    <div class="pending-user-timer-text" style="margin-top:6px; font-size:12px; color:#374151;">00:00</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endauth

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
            <div class="card donation-form-card" style="margin-top: 24px;">
                <div class="donation-form-carousel">
                    <h3>Faire un don</h3>
                </div>
                <form method="POST" action="{{ route('donations.store') }}">
                    @csrf

                    <div class="form-group">
                        <label for="phone">Numéro Mobile Money (RDC)</label>
                        <input id="phone" name="phone" type="tel" value="{{ app()->environment('local') ? '243815000001' : '' }}" placeholder="243815000001" pattern="(?:243|0)[0-9]{9}" required>
                    </div>

                    <div class="form-group">
                        <label for="provider">Opérateur</label>
                        <select id="provider" name="provider" required>
                            <option value="VODACOM">Vodacom</option>
                            <option value="ORANGE">Orange</option>
                            <option value="AIRTEL">Airtel</option>
                            <option value="AFRICELL">Africell</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="amount">Montant</label>
                        <input id="amount" name="amount" type="number" min="1" step="1" placeholder="1000" required>
                    </div>

                    <div class="form-group">
                        <label for="currency">Devise</label>
                        <select id="currency" name="currency" required>
                            <option value="CDF">CDF</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="country">Pays</label>
                        <select id="country" name="country" required>
                            <option value="CD">RD Congo</option>
                        </select>
                    </div>

                    <p class="muted">Paiement sécurisé par PawaPay. En développement, utilisez un numéro de test RDC.</p>

                    <button type="submit" class="btn">Créer le paiement</button>
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
            const phoneInput = document.querySelector('#phone');
            const providerInput = document.querySelector('#provider');
            const testPhones = {
                VODACOM: '243815000001',
                ORANGE: '243898000001',
                AIRTEL: '243990000001',
                AFRICELL: '243900000001'
            };
            if (phoneInput && providerInput && @json(app()->environment('local'))) {
                providerInput.addEventListener('change', function () {
                    phoneInput.value = testPhones[providerInput.value] || '';
                });
            }
            bars.forEach(function (bar) {
                const donation = bar.closest('[data-pending-donation]');
                const createdAt = new Date(bar.dataset.createdAt).getTime();
                const reference = bar.dataset.reference;
                const durationMs = 15 * 60 * 1000;
                const paymentButton = donation.querySelector('[data-pawapay-pay]');
                const statusText = donation.querySelector('[data-payment-status]');
                const errorText = donation.querySelector('[data-payment-error]');

                function pay() {
                    paymentButton.disabled = true;
                    statusText.textContent = 'Initialisation...';
                    errorText.style.display = 'none';

                    const csrfToken = document.querySelector('meta[name="csrf-token"]');
                    if (!csrfToken || !csrfToken.content) {
                        statusText.textContent = 'Échec';
                        errorText.textContent = 'Session expirée. Rechargez la page puis réessayez.';
                        errorText.style.display = 'block';
                        paymentButton.disabled = false;
                        return;
                    }

                    fetch('/api/pawapay/deposit', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken.content
                        },
                        body: JSON.stringify({
                            orderId: donation.dataset.orderId,
                            phone: donation.dataset.phone,
                            provider: {
                                VODACOM: 'MPESA_COD',
                                ORANGE: 'ORANGE_COD',
                                AIRTEL: 'AIRTEL_COD',
                                AFRICELL: 'AFRICELL_COD'
                            }[donation.dataset.provider] || donation.dataset.provider,
                            amount: Number(donation.dataset.amount)
                        })
                    })
                    .then(function (response) {
                        return response.text().then(function (body) {
                            let data = {};
                            try {
                                data = body ? JSON.parse(body) : {};
                            } catch (parseError) {
                                throw new Error('Réponse invalide du serveur (' + response.status + ').');
                            }
                            if (!response.ok) {
                                throw new Error(data.message || 'Le paiement n’a pas pu être initialisé.');
                            }
                            return data;
                        });
                    })
                    .then(function () {
                        statusText.textContent = 'En attente de confirmation';
                        paymentButton.style.display = 'none';
                    })
                    .catch(function (error) {
                        statusText.textContent = 'Échec';
                        errorText.textContent = error.message;
                        errorText.style.display = 'block';
                        paymentButton.disabled = false;
                    });
                }

                paymentButton.addEventListener('click', pay);

                function update() {
                    const now = Date.now();
                    const elapsed = Math.max(0, now - createdAt);
                    const remaining = Math.max(0, durationMs - elapsed);
                    const ratio = Math.min(100, (elapsed / durationMs) * 100);

                    if (remaining <= 0) {
                        const donation = bar.closest('[data-pending-donation]');
                        if (donation) {
                            donation.remove();
                        }
                        return;
                    }

                    bar.style.width = ratio + '%';
                    const text = bar.parentElement.parentElement.querySelector('.pending-user-timer-text');
                    if (text) {
                        const totalSeconds = Math.ceil(remaining / 1000);
                        const minutes = String(Math.floor(totalSeconds / 60)).padStart(2, '0');
                        const seconds = String(totalSeconds % 60).padStart(2, '0');
                        text.textContent = minutes + ':' + seconds;
                    }
                }

                function pollStatus() {
                    if (!reference) {
                        return;
                    }

                    fetch('/dons/status/' + encodeURIComponent(reference), {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(function (response) {
                        if (!response.ok) {
                            return null;
                        }

                        return response.json();
                    })
                    .then(function (data) {
                        if (!data || !data.success) {
                            return;
                        }

                        const status = String(data.status || '').toLowerCase();
                        if (['paid', 'success', 'completed', 'confirmed'].includes(status)) {
                            window.location.reload();
                        } else if (['failed', 'rejected', 'cancelled'].includes(status)) {
                            statusText.textContent = 'Échec';
                            paymentButton.style.display = 'inline-block';
                            paymentButton.disabled = false;
                        }
                    })
                    .catch(function () {
                        // Ignore polling errors and continue retrying until the payment resolves.
                    });
                }

                update();
                setInterval(update, 1000);
                pollStatus();
                setInterval(pollStatus, 5000);
            });
        });
    </script>
@endsection
