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
            'provider' => ['required', 'string', 'in:VODACOM,ORANGE,AIRTEL,AFRICELL'],
        ]);

        $phone = preg_replace('/\D+/', '', $validated['phone']);
        if (str_starts_with($phone, '0')) {
            $phone = '243' . substr($phone, 1);
        } elseif (! str_starts_with($phone, '243')) {
            $phone = '243' . $phone;
        }

        if (! preg_match('/^243[0-9]{9}$/', $phone) || strtoupper($validated['currency']) !== 'CDF') {
            return back()->withInput()->with('error', 'Utilisez un numéro RDC valide et la devise CDF.');
        }

        $donation = Donation::create([
            'user_id' => Auth::id(),
            'amount' => $validated['amount'],
            'currency' => strtoupper($validated['currency']),
            'phone' => $phone,
            'provider' => strtolower($validated['provider']),
            'status' => 'pending',
            'external_reference' => null,
        ]);

        return redirect()->route('donations.index')->with('success', 'Commande créée. Lancez le paiement Mobile Money.');
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
        if (! $donation) {
            return response()->json([
                'success' => false,
                'message' => 'Paiement introuvable.',
            ], 404);
        }

        if ($donation->provider !== 'easypay') {
            return response()->json([
                'success' => $donation->status === 'paid',
                'status' => $donation->status,
                'reference' => $reference,
            ]);
        }

        $paid = (new EasyPayGateway())->verifyPayment($reference);

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
