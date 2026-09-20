<?php

namespace App\Services;

use App\Models\Donation;
use Illuminate\Support\Str;

class PaymentService
{
    public function createPayment(Donation $order, string $method = 'EASYPAY'): array
    {
        $method = strtoupper($method);

        if ($method === 'UNIPAY') {
            $result = $this->createUniPayPayment($order);
            if (($result['success'] ?? false) === true) {
                $order->update(['provider' => 'unipay']);
            }

            return $result;
        }

        if ($method !== 'EASYPAY') {
            return ['success' => false, 'message' => 'Méthode de paiement inconnue.'];
        }

        $reference = (string) ($order->external_reference ?: 'order_' . Str::uuid());
        $appUrl = rtrim((string) env('APP_URL', config('app.url')), '/');

        $result = (new EasyPayGateway())->initPayment([
            'order_ref' => $reference,
            'amount' => (float) $order->amount,
            'currency' => strtoupper((string) $order->currency),
            'description' => 'Don Conza #' . $order->id,
            'success_url' => $appUrl . '/payment/success?reference=' . rawurlencode($reference) . '&provider=EASYPAY',
            'error_url' => $appUrl . '/payment/error',
            'cancel_url' => $appUrl . '/payment/cancel',
            'language' => 'fr',
            'channels' => request()->input('channels', ['mobile_money']),
            'customer_name' => optional($order->user)->name,
            'customer_email' => optional($order->user)->email,
        ]);

        if (($result['success'] ?? false) === true) {
            $order->update([
                'provider' => 'easypay',
                'external_reference' => $result['reference'],
            ]);

            return $result;
        }

        if (UniPayGateway::enabled()) {
            $fallback = $this->createUniPayPayment($order);
            if (($fallback['success'] ?? false) === true) {
                $order->update([
                    'provider' => 'unipay',
                    'external_reference' => $fallback['reference'] ?? $order->external_reference,
                ]);
            }

            return $fallback;
        }

        return $result;
    }

    private function createUniPayPayment(Donation $order): array
    {
        return (new UniPayGateway())->createPayment(
                (float) $order->amount,
                (string) $order->external_reference,
                request()->input('phone'),
                request()->input('operator', 'orange'),
                request()->input('direction', 'collect'),
                (string) $order->currency,
                request()->input('country', 'CD')
            );
    }
}