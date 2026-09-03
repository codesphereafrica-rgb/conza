<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Models\Setting;
use App\Services\LabyrintheGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DonationController extends Controller
{
    private function finalizePendingDonations(): void
    {
        Donation::where('status', 'pending')
            ->where('created_at', '<=', now()->subMinutes(1))
            ->update(['status' => 'paid']);
    }

    public function index()
    {
        $this->finalizePendingDonations();

        $goalValue = Setting::get('fundraising_goal', null);
        $target = $goalValue !== null ? (float) $goalValue : null;

        $collected = Donation::where('status', 'paid')->sum('amount');

        if ($target && $target > 0) {
            $progress = min(($collected / $target) * 100, 100);
        } else {
            $progress = 0;
        }

        $byStatus = Donation::select('status', DB::raw('COUNT(*) as count'), DB::raw('COALESCE(SUM(amount),0) as total'))
            ->groupBy('status')
            ->get();

        $recentDonations = Donation::with('user')->latest()->take(10)->get();

        $userPendingDonations = Auth::check()
            ? Donation::where('user_id', Auth::id())
                ->where('status', 'pending')
                ->orderByDesc('created_at')
                ->get()
            : collect();

        $labyrintheEnabled = LabyrintheGateway::enabled();

        return view('donations.index', compact('target', 'collected', 'progress', 'byStatus', 'recentDonations', 'userPendingDonations', 'labyrintheEnabled'));
    }

    public function markPaid(Donation $donation)
    {
        $user = auth()->user();
        if (!$user || ($user->role ?? null) !== 'admin') {
            abort(403, 'Action réservée aux administrateurs.');
        }

        $donation->status = 'paid';
        $donation->save();

        return back()->with('success', 'Don marqué comme payé.');
    }

    public function markPending(Donation $donation)
    {
        $user = auth()->user();
        if (!$user || ($user->role ?? null) !== 'admin') {
            abort(403, 'Action réservée aux administrateurs.');
        }

        $donation->status = 'pending';
        $donation->save();

        return back()->with('success', 'Statut du don remis à pending.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'phone' => ['required', 'string', 'min:8'],
            'currency' => ['required', 'string', 'in:CDF,USD'],
            'country' => ['required', 'string', 'in:CD'],
        ]);

        $phone = preg_replace('/\D+/', '', (string) $validated['phone']);

        $donation = Donation::create([
            'user_id' => Auth::id(),
            'amount' => $validated['amount'],
            'provider' => 'labyrinthe',
            'status' => 'pending',
            'external_reference' => 'don_' . time() . '_' . Auth::id(),
        ]);

        if (LabyrintheGateway::enabled()) {
            $gateway = new LabyrintheGateway();
            $result = $gateway->createPayment(
                (float) $validated['amount'],
                $donation->external_reference,
                optional(Auth::user())->email,
                $phone,
                $validated['currency'],
                $validated['country']
            );

            if (($result['success'] ?? false) === true) {
                $donation->update([
                    'provider' => 'labyrinthe',
                    'external_reference' => $result['reference'] ?? $donation->external_reference,
                ]);

                return redirect()->route('donations.index')->with('success', $result['message'] ?? 'Votre transaction a bien été initiée.');
            }

            return back()->with('error', $result['message'] ?? 'Le paiement Labyrinthe est indisponible pour le moment.');
        }

        $donation->update(['provider' => 'manual']);

        return redirect()->route('donations.index')->with('success', 'Votre contribution a bien été enregistrée en attente de validation.');
    }

    public function callback(Request $request, string $reference)
    {
        $donation = Donation::where('external_reference', $reference)->first();

        if (!$donation) {
            abort(404, 'Donation introuvable.');
        }

        $status = $request->input('status', 'paid');
        $donation->status = strtolower($status) === 'paid' ? 'paid' : 'pending';
        $donation->save();

        return redirect()->route('donations.index')->with('success', 'Paiement confirmé. Merci pour votre soutien.');
    }

    public function mobileCallback(Request $request)
    {
        $status = strtolower((string) $request->input('status', 'paid'));
        $reference = (string) $request->input('reference', '');

        if ($reference !== '') {
            $donation = Donation::where('external_reference', $reference)->first();
            if ($donation) {
                $donation->status = $status === 'paid' ? 'paid' : 'pending';
                $donation->save();
            }
        }

        return redirect()->route('donations.index')->with('success', 'Paiement Labyrinthe traité. Merci pour votre soutien.');
    }
}
