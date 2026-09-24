<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PawaPayService
{
    private const PROVIDERS = ['VODACOM', 'ORANGE', 'AIRTEL', 'AFRICELL'];

    public function createDeposit(string $phone, string $provider, string $amount, string $depositId): array
    {
        $provider = strtoupper($provider);
        if (! in_array($provider, self::PROVIDERS, true)) {
            throw new RuntimeException('Opérateur Mobile Money invalide.');
        }

        $correspondent = $provider === 'VODACOM' ? 'MPESA_COD' : $provider;

        $apiKey = (string) config('services.pawapay.api_key');
        if ($apiKey === '') {
            Log::error('PawaPay API key missing on Render', [
                'env_present' => getenv('PAWAPAY_API_KEY') !== false,
                'config_present' => (bool) config('services.pawapay.api_key'),
            ]);
            throw new RuntimeException('Clé API manquante sur Render');
        }

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.pawapay.timeout', 20))
            ->post(rtrim((string) config('services.pawapay.base_url'), '/') . '/v2/deposits', [
                'depositId' => $depositId,
                'amount' => (string) $amount,
                'currency' => 'CDF',
                'country' => 'COD',
                'correspondent' => $correspondent,
                'payer' => [
                    'type' => 'MSISDN',
                    'address' => ['value' => $phone],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('PawaPay a refusé la demande (' . $response->status() . ').');
        }

        return $response->json() ?? [];
    }

    public static function providers(): array
    {
        return self::PROVIDERS;
    }
}
