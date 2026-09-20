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

        $rawBody = $response->body();
        Log::info('[EASYPAY INIT RAW] ' . $rawBody);
        $data = json_decode($rawBody, true);
        $data = is_array($data) ? $data : [];
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

        $paymentUrl = $data['checkout_url']
            ?? $data['payment_url']
            ?? $data['redirect_url']
            ?? $data['url']
            ?? data_get($data, 'data.checkout_url')
            ?? data_get($data, 'data.payment_url')
            ?? data_get($data, 'data.redirect_url')
            ?? data_get($data, 'data.url');

        return [
            'success' => true,
            'paymentUrl' => $paymentUrl,
            'reference' => (string) $reference,
            'authToken' => $data['auth_token'] ?? data_get($data, 'data.auth_token'),
            'response' => $data,
        ];
    }

    public function pushPayment(string $reference, string $phone, string $channel): array
    {
        if (! self::enabled() || $reference === '' || $phone === '' || $channel === '') {
            return ['success' => false, 'message' => 'Paramètres EasyPay mobile-money incomplets.'];
        }

        $endpoint = rtrim((string) config('services.easypay.base_url'), '/')
            . '/' . trim((string) config('services.easypay.version'), '/')
            . '/payment/' . rawurlencode($reference) . '/mobile-money';

        try {
            $response = Http::acceptJson()->timeout(15)->post($endpoint, [
                'phone' => $phone,
                'channel' => $channel,
            ]);
        } catch (\Throwable $exception) {
            Log::error('EasyPay mobile-money push failed.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Le push USSD EasyPay est indisponible.'];
        }

        $rawBody = $response->body();
        Log::info('EasyPay mobile-money push response.', ['body' => $rawBody]);
        $data = json_decode($rawBody, true);
        $data = is_array($data) ? $data : [];
        $success = $response->successful() && (($data['success'] ?? true) !== false);

        return [
            'success' => $success,
            'message' => $data['message'] ?? ($success ? 'Push USSD EasyPay envoyé.' : 'Échec du push USSD EasyPay.'),
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