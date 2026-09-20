<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class EasyPayGateway
{
    public static function enabled(): bool
    {
        return filled(config('services.easypay.cid'))
            && filled(config('services.easypay.token'))
            && filled(config('services.easypay.version'));
    }

    public function initPayment(array $payload): array
    {
        if (! self::enabled()) {
            return ['success' => false, 'message' => 'EasyPay n\'est pas configuré.'];
        }

        $endpoint = rtrim((string) config('services.easypay.base_url'), '/')
            . '/' . trim((string) config('services.easypay.version'), '/')
            . '/payment/initialization?'
            . http_build_query([
                'cid' => config('services.easypay.cid'),
                'token' => config('services.easypay.token'),
            ]);

        $response = Http::acceptJson()->timeout(15)->post($endpoint, $payload);

        $data = $response->json() ?? [];
        $reference = $data['reference'] ?? data_get($data, 'data.reference');

        if (! $response->successful() || ! $reference) {
            return [
                'success' => false,
                'message' => $data['message'] ?? 'Échec de l\'initialisation EasyPay.',
                'response' => $data,
            ];
        }

        $paymentUrl = $data['payment_url']
            ?? $data['paymentUrl']
            ?? data_get($data, 'data.payment_url')
            ?? rtrim((string) config('services.easypay.base_url'), '/')
                . '/' . trim((string) config('services.easypay.version'), '/')
                . '/payment/initialization?reference=' . rawurlencode((string) $reference);

        return [
            'success' => true,
            'paymentUrl' => $paymentUrl,
            'reference' => (string) $reference,
            'response' => $data,
        ];
    }

    public function verifyPayment(string $reference): bool
    {
        if (! self::enabled() || $reference === '') {
            return false;
        }

        $endpoint = rtrim((string) config('services.easypay.base_url'), '/')
            . '/' . trim((string) config('services.easypay.version'), '/')
            . '/payment/' . rawurlencode($reference) . '/checking-status';

        $response = Http::acceptJson()->timeout(15)->get($endpoint);
        $data = $response->json() ?? [];
        $status = strtoupper((string) ($data['status'] ?? data_get($data, 'data.status') ?? ''));

        return $response->successful() && in_array($status, ['SUCCESS', 'PAID'], true);
    }
}