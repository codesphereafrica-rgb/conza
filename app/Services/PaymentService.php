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

        $result = (new EasyPayGateway())->initPayment([
            'order_ref' => $reference,
            'amount' => (int) round((float) $order->amount),
            'currency' => 'CDF',
            'description' => 'Conza',
            'customer_name' => optional($order->user)->name,
            'customer_email' => optional($order->user)->email,
            'customer_phone' => $normalizedPhone,
            'success_url' => $appUrl . '/payment/success',
            'error_url' => $appUrl . '/payment/error',
            'cancel_url' => $appUrl . '/payment/cancel',
            'language' => 'fr',
            'channels' => ['MOBILE_MONEY'],
        ]);

        if (($result['success'] ?? false) === true) {
            $order->update([
                'provider' => 'easypay',
                'external_reference' => $result['reference'],
            ]);

            return $result;
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

}