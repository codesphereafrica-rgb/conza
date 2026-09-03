<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class LabyrintheGateway
{
    public static function enabled(): bool
    {
        return !empty(config('services.labyrinthe.api_url')) && !empty(config('services.labyrinthe.api_token'));
    }

    public function createPayment(float $amount, string $reference, ?string $email = null, ?string $phone = null, ?string $currency = null, ?string $country = null): array
    {
        if (!self::enabled()) {
            return [
                'enabled' => false,
                'message' => 'Le paiement Labyrinthe n\'est pas encore configuré.',
            ];
        }

        $phone = preg_replace('/\D+/', '', (string) ($phone ?? ''));
        $callbackUrl = url('/callback');
        $baseUrl = rtrim((string) config('services.labyrinthe.api_url'), '/');
        $candidateUrls = array_values(array_unique([
            $baseUrl,
            $baseUrl . '/mobile',
            str_replace('/payment', '/payment/mobile', $baseUrl),
            str_replace('/mobile', '', $baseUrl),
        ]));

        $payload = [
            'token' => config('services.labyrinthe.api_token'),
            'phone' => $phone ?: null,
            'amount' => (int) round($amount),
            'currency' => strtoupper($currency ?? config('services.labyrinthe.currency', 'CDF')),
            'country' => strtoupper($country ?? 'CD'),
            'callback' => $callbackUrl,
        ];

        $payload = array_filter($payload, fn ($value) => $value !== null && $value !== '');

        $response = null;
        $lastErrorBody = null;

        foreach ($candidateUrls as $url) {
            $response = Http::acceptJson()->post($url, $payload);

            if ($response->successful()) {
                break;
            }

            $lastErrorBody = $response->json();

            if ($response->status() === 404) {
                continue;
            }

            break;
        }

        if (! $response || ! $response->successful()) {
            $message = $lastErrorBody['message'] ?? 'Échec du paiement Labyrinthe mobile.';

            return [
                'enabled' => true,
                'success' => false,
                'message' => $message,
            ];
        }

        $data = $response->json();
        $success = (bool) ($data['success'] ?? false);

        return [
            'enabled' => true,
            'success' => $success,
            'message' => $data['message'] ?? ($success ? 'Transaction Labyrinthe initiée.' : 'Erreur Labyrinthe.'),
            'reference' => $data['reference'] ?? $reference,
            'orderNumber' => $data['orderNumber'] ?? null,
            'response' => $data,
        ];
    }
}
