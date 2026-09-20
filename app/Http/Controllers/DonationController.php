<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Models\Setting;
use App\Services\PaymentService;
use App\Services\EasyPayGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DonationController extends Controller
{
    public function index()
    {
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
                ->where('created_at', '>', now()->subMinute())
                ->orderByDesc('created_at')
                ->get()
            : collect();

        return view('donations.index', compact('target', 'collected', 'progress', 'byStatus', 'recentDonations', 'userPendingDonations'));
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
            'phone' => ['required', 'string', 'min:9'],
            'currency' => ['required', 'string', 'in:CDF,USD'],
            'country' => ['required', 'string', 'in:CD'],
            'payment_method' => ['sometimes', 'string', 'in:EASYPAY'],
        ]);

        $donation = Donation::create([
            'user_id' => Auth::id(),
            'amount' => $validated['amount'],
            'currency' => strtoupper($validated['currency']),
            'provider' => 'easypay',
            'status' => 'pending',
            'external_reference' => 'don_' . time() . '_' . Auth::id(),
        ]);

        $result = (new PaymentService())->createPayment($donation, $validated['phone'], $validated['payment_method'] ?? 'EASYPAY');

        if (($result['success'] ?? false) === true) {
            if (! empty($result['paymentUrl'])) {
                return response()->view('payments.redirect', [
                    'paymentUrl' => $result['paymentUrl'],
                    'authToken' => $result['authToken'] ?? $result['reference'],
                    'postBackUrl' => rtrim((string) env('APP_URL', config('app.url')), '/'),
                ]);
            }

            return redirect()->route('donations.index')->with('success', $result['message'] ?? 'Votre transaction a bien été initiée.');
        }

        return back()->withInput()->with('error', $result['message'] ?? 'Le paiement est indisponible pour le moment.');
    }

    public function callback(Request $request, string $reference)
    {
        $reference = (string) $request->query('ref', $reference);
        $donation = Donation::where('external_reference', $reference)->first();

        if (!$donation) {
            abort(404, 'Donation introuvable.');
        }

        $status = strtolower((string) $request->input('status', 'pending'));
        $donation->status = in_array($status, ['paid', 'success', 'completed', 'confirmed'], true)
            ? 'paid'
            : 'pending';
        $donation->save();

        $message = $donation->status === 'paid'
            ? 'Paiement confirmé. Merci pour votre soutien.'
            : 'Paiement reçu, en attente de confirmation par l’opérateur.';

        return redirect()->route('donations.index')->with('success', $message);
    }

    public function checkStatus(Request $request, string $reference)
    {
        $donation = Donation::where('external_reference', $reference)->first();
        $paid = $donation && $donation->provider === 'easypay'
            ? (new EasyPayGateway())->verifyPayment($reference)
            : false;

        if (! $donation) {
            return response()->json([
                'success' => false,
                'message' => 'Paiement introuvable.',
            ], 404);
        }

        if ($paid) {
            $donation->status = 'paid';
            $donation->save();
        }

        return response()->json([
            'success' => $paid,
            'status' => $donation->fresh()->status,
            'reference' => $reference,
        ], $paid ? 200 : 422);
    }

    public function mobileCallback(Request $request)
    {
        $status = strtolower((string) $request->input('status', 'pending'));
        $reference = (string) $request->input('reference', '');

        if ($reference !== '') {
            $donation = Donation::where('external_reference', $reference)->first();
            if ($donation) {
                $donation->status = in_array($status, ['paid', 'success', 'completed', 'confirmed'], true)
                    ? 'paid'
                    : 'pending';
                $donation->save();
            }
        }

        $message = in_array($status, ['paid', 'success', 'completed', 'confirmed'], true)
            ? 'Paiement Unipay confirmé. Merci pour votre soutien.'
            : 'Demande Unipay reçue, en attente de confirmation par l’opérateur.';

        return redirect()->route('donations.index')->with('success', $message);
    }
}
