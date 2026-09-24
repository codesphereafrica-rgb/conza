<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Services\PawaPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

class PawaPayController extends Controller
{
    public function deposit(Request $request, PawaPayService $pawaPay)
    {
        $data = $request->validate([
            'phone' => ['required', 'regex:/^243[0-9]{9}$/'],
            'provider' => ['required', Rule::in(PawaPayService::providers())],
            'amount' => ['required', 'numeric', 'min:1'],
            'orderId' => ['required', 'integer'],
        ]);

        $donation = Donation::whereKey($data['orderId'])
            ->where('user_id', $request->user()->id)
            ->where('status', 'pending')
            ->firstOrFail();

        if ((float) $donation->amount !== (float) $data['amount'] || strtoupper($donation->currency) !== 'CDF') {
            return response()->json(['message' => 'Le montant de la commande ne correspond pas.'], 422);
        }

        $depositId = 'CONZA-' . (int) floor(microtime(true) * 1000) . '-' . substr(base_convert((string) random_int(0, PHP_INT_MAX), 10, 36), 0, 8);

        try {
            $result = $pawaPay->createDeposit(
                $data['phone'],
                $data['provider'],
                rtrim(rtrim(number_format((float) $donation->amount, 2, '.', ''), '0'), '.'),
                $depositId
            );
        } catch (Throwable $exception) {
            Log::error('PawaPay deposit failed', [
                'order_id' => $donation->id,
                'deposit_id' => $depositId,
                'error' => $exception->getMessage(),
            ]);

            return response()->json(['message' => $exception->getMessage()], 502);
        }

        $actualDepositId = (string) ($result['depositId'] ?? $depositId);

        $donation->update([
            'provider' => strtolower($data['provider']),
            'external_reference' => $actualDepositId,
        ]);

        return response()->json([
            'depositId' => $actualDepositId,
            'status' => $result['status'] ?? 'ACCEPTED',
            'orderId' => $donation->id,
            'pawapay' => $result,
        ]);
    }

    public function callback(Request $request)
    {
        $rawPayload = $request->getContent();
        $signature = (string) $request->header('X-PawaPay-Signature', '');
        $webhookSecret = (string) config('services.pawapay.webhook_secret');

        if ($webhookSecret !== '') {
            $expected = hash_hmac('sha256', $rawPayload, $webhookSecret);
            if ($signature === '' || ! hash_equals($expected, $signature)) {
                Log::warning('Invalid PawaPay webhook signature.');
                return response()->json(['message' => 'Invalid signature.'], 401);
            }
        }

        $payload = $request->json()->all();
        Log::info('PawaPay webhook received', ['payload' => $payload]);

        $depositId = (string) data_get($payload, 'depositId', data_get($payload, 'deposit_id', ''));
        $status = strtoupper((string) data_get($payload, 'status', ''));
        $donation = $depositId !== ''
            ? Donation::where('external_reference', $depositId)->first()
            : null;

        if ($donation && $status === 'COMPLETED') {
            $donation->update(['status' => 'paid']);
        } elseif ($donation && in_array($status, ['FAILED', 'REJECTED', 'CANCELLED'], true) && $donation->status !== 'paid') {
            $donation->update(['status' => 'failed']);
        }

        return response()->json(['received' => true]);
    }
}
