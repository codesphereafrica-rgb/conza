<?php

namespace App\Services;

use App\Models\Donation;

class PaymentService
{
    public function createPayment(Donation $order, string $phone, string $method = 'EASYPAY'): array
    {
        $method = strtoupper($method);

        if ($method !== 'EASYPAY') {
            return ['success' => false, 'message' => 'Seul le paiement EasyPay est disponible.'];
        }

        $reference = $order->id . '-' . (int) floor(microtime(true) * 1000);
        $appUrl = rtrim((string) env('APP_URL', config('app.url')), '/');
        $normalizedPhone = $this->normalizePhone($phone);
        $channel = $this->detectChannel($normalizedPhone);

        $result = (new EasyPayGateway())->initPayment([
            'order_ref' => $reference,
            'amount' => (int) round((float) $order->amount),
            'currency' => 'CDF',
            'description' => 'Don Conza #' . $order->id,
            'customer_name' => optional($order->user)->name,
            'customer_email' => optional($order->user)->email,
            'customer_phone' => $normalizedPhone,
            'success_url' => $appUrl . '/payment/success?ref=' . rawurlencode($reference),
            'error_url' => $appUrl . '/payment/error',
            'cancel_url' => $appUrl . '/payment/cancel',
            'language' => 'fr',
            'channels' => ['AIRTEL_MONEY', 'ORANGE_MONEY', 'M_PESA'],
        ]);

        if (($result['success'] ?? false) === true) {
            $order->update([
                'provider' => 'easypay',
                'external_reference' => $result['reference'],
            ]);

            if (! empty($result['paymentUrl'])) {
                return $result;
            }

            $push = (new EasyPayGateway())->pushPayment($result['reference'], $normalizedPhone, $channel);
            if (($push['success'] ?? false) !== true) {
                return [
                    'success' => false,
                    'message' => $push['message'] ?? 'Le paiement est initialisé mais le push USSD a échoué.',
                    'reference' => $result['reference'],
                ];
            }

            return $result + ['push' => $push];
        }

        return $result;
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($digits, '0')) {
            return '243' . substr($digits, 1);
        }

        return str_starts_with($digits, '243') ? $digits : '243' . $digits;
    }

    private function detectChannel(string $phone): string
    {
        $localNumber = substr($phone, 3, 2);

        return match (true) {
            in_array($localNumber, ['97', '99'], true) => 'AIRTEL_MONEY',
            in_array($localNumber, ['80', '81', '82', '83'], true) => 'M_PESA',
            default => 'ORANGE_MONEY',
        };
    }
}