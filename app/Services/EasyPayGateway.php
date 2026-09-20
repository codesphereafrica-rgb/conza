<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

        try {
            $response = Http::acceptJson()->timeout(15)->post($endpoint, $payload);
        } catch (\Throwable $exception) {
            Log::error('EasyPay initialization request failed.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return ['success' => false, 'message' => 'EasyPay est momentanément indisponible.'];
        }

        $data = $response->json() ?? [];
        $reference = $data['reference'] ?? data_get($data, 'data.reference');

        if (! $response->successful() || ! $reference) {
            Log::error('EasyPay initialization rejected.', [
                'status' => $response->status(),
                'message' => $data['message'] ?? null,
            ]);

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

        try {
            $response = Http::acceptJson()->timeout(15)->get($endpoint);
        } catch (\Throwable $exception) {
            Log::error('EasyPay status request failed.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
        $data = $response->json() ?? [];
        $status = strtoupper((string) ($data['status'] ?? data_get($data, 'data.status') ?? ''));

        if (! $response->successful()) {
            Log::error('EasyPay status request rejected.', [
                'status' => $response->status(),
                'message' => $data['message'] ?? null,
            ]);
        }

        return $response->successful() && in_array($status, ['SUCCESS', 'PAID'], true);
    }
}