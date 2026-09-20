<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Services\EasyPayGateway;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function init(Request $request)
    {
        $data = $request->validate([
            'orderId' => ['required', 'integer'],
            'paymentMethod' => ['sometimes', 'string', 'in:EASYPAY'],
        ]);

        $order = Donation::whereKey($data['orderId'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
        $result = (new PaymentService())->createPayment($order, $data['paymentMethod'] ?? 'EASYPAY');

        if (($result['success'] ?? false) !== true) {
            return response()->json($result, 422);
        }

        return response()->json([
            'paymentUrl' => $result['paymentUrl'] ?? null,
            'reference' => $result['reference'] ?? $order->external_reference,
        ]);
    }

    public function verify(string $reference)
    {
        $order = Donation::where('external_reference', $reference)->first();
        if (! $order) {
            return response()->json([
                'success' => false,
                'status' => 'unknown',
                'reference' => $reference,
                'message' => 'Paiement introuvable.',
            ], 404);
        }

        $paid = (new EasyPayGateway())->verifyPayment($reference);
        $paid = $paid === true || in_array(strtoupper((string) $paid), ['SUCCESS', 'PAID', 'COMPLETED', 'CONFIRMED'], true);

        if ($order && $paid) {
            $order->update(['status' => 'paid']);
        }

        return response()->json([
            'success' => $paid,
            'status' => $order?->fresh()->status ?? 'unknown',
            'reference' => $reference,
        ], $paid ? 200 : 422);
    }

    public function success(Request $request)
    {
        $reference = (string) $request->query('reference', '');
        $paid = $reference !== '' && (new EasyPayGateway())->verifyPayment($reference);
        $order = Donation::where('external_reference', $reference)->first();

        if ($order && $paid) {
            $order->update(['status' => 'paid']);
        }

        return view('payments.success', compact('reference', 'paid'));
    }

    public function failure(Request $request, string $status)
    {
        $reference = (string) $request->query('reference', '');
        $order = $reference !== '' ? Donation::where('external_reference', $reference)->first() : null;

        if ($order && $order->status !== 'paid') {
            $order->update(['status' => $status]);
        }

        return view('payments.' . $status, compact('reference'));
    }
}