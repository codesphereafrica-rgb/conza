<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class UniPayGateway
{
    public static function enabled(): bool
    {
        return !empty(config('services.unipay.base_url')) && !empty(config('services.unipay.api_key'));
    }

    protected function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) ($phone ?? ''));

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '243')) {
            return '+' . $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '+243' . substr($digits, 1);
        }

        return '+' . $digits;
    }

    public function createPayment(float $amount, string $reference, ?string $phone = null, ?string $operator = 'orange', ?string $direction = 'collect'): array
    {
        if (!self::enabled()) {
            return [
                'enabled' => false,
                'message' => 'Le paiement Unipay n\'est pas encore configuré.',
            ];
        }

        $normalizedPhone = $this->normalizePhone($phone);
        $baseUrl = rtrim((string) config('services.unipay.base_url', 'https://unipay-api.onrender.com'), '/');
        $payload = [
            'operator' => strtolower((string) ($operator ?? 'orange')),
            'phone' => $normalizedPhone,
            'amount' => (int) round($amount),
            'reference' => $reference,
            'direction' => strtolower((string) ($direction ?? 'collect')),
        ];

        $payload = array_filter($payload, fn ($value) => $value !== null && $value !== '');

        $response = Http::acceptJson()
            ->withHeaders([
                'X-API-Key' => (string) config('services.unipay.api_key'),
            ])
            ->post($baseUrl . '/v1/payment/initiate', $payload);

        if (! $response->successful()) {
            $body = $response->json();

            return [
                'enabled' => true,
                'success' => false,
                'message' => $body['message'] ?? 'Échec du paiement Unipay.',
                'response' => $body,
            ];
        }

        $data = $response->json();
        $success = (bool) ($data['success'] ?? ($data['status'] ?? null) === 'success');

        return [
            'enabled' => true,
            'success' => $success,
            'message' => $data['message'] ?? ($success ? 'Transaction Unipay initiée.' : 'Erreur Unipay.'),
            'reference' => $data['reference'] ?? $reference,
            'payment_url' => $data['payment_url'] ?? $data['checkout_url'] ?? $data['url'] ?? null,
            'response' => $data,
        ];
    }

    public function checkStatus(string $reference): array
    {
        $baseUrl = rtrim((string) config('services.unipay.base_url', 'https://unipay-api.onrender.com'), '/');

        $response = Http::acceptJson()
            ->withHeaders([
                'X-API-Key' => (string) config('services.unipay.api_key'),
            ])
            ->get($baseUrl . '/v1/payment/status/' . rawurlencode($reference));

        if (! $response->successful()) {
            return [
                'success' => false,
                'message' => $response->json()['message'] ?? 'Impossible de vérifier le statut du paiement.',
                'response' => $response->json(),
            ];
        }

        return [
            'success' => true,
            'response' => $response->json(),
        ];
    }
}
