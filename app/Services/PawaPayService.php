<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class PawaPayService
{
    private const PROVIDERS = ['VODACOM', 'ORANGE', 'AIRTEL', 'AFRICELL', 'MPESA_COD', 'AIRTEL_COD', 'ORANGE_COD', 'AFRICELL_COD'];

    public function createDeposit(string $phone, string $provider, string $amount, string $depositId): array
    {
        $provider = strtoupper($provider);
        if (! in_array($provider, self::PROVIDERS, true)) {
            throw new RuntimeException('Opérateur Mobile Money invalide.');
        }

        $correspondents = match ($provider) {
            'VODACOM', 'MPESA_COD' => ['MPESA_COD', 'VODACOM_COD'],
            'AIRTEL', 'AIRTEL_COD' => ['AIRTEL_COD', 'AIRTEL_OAPI_COD'],
            'ORANGE', 'ORANGE_COD' => ['ORANGE_COD'],
            'AFRICELL', 'AFRICELL_COD' => ['AFRICELL_COD'],
        };

        $apiKey = trim((string) config('services.pawapay.api_key'));
        if ($apiKey === '') {
            Log::error('PawaPay API key missing on Render', [
                'env_present' => getenv('PAWAPAY_API_KEY') !== false,
                'config_present' => (bool) config('services.pawapay.api_key'),
            ]);
            throw new RuntimeException('Clé API manquante sur Render');
        }

        $http = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.pawapay.timeout', 20));
        $depositUrl = rtrim((string) config('services.pawapay.base_url'), '/') . '/v2/deposits';

        foreach ($correspondents as $index => $correspondent) {
            $depositId = Str::uuid()->toString();
            $payload = [
                'depositId' => $depositId,
                'amount' => (string) $amount,
                'currency' => 'CDF',
                'payer' => [
                    'type' => 'MMO',
                    'accountDetails' => [
                        'phoneNumber' => $phone,
                        'provider' => $correspondent,
                    ],
                ],
                'customerMessage' => 'CONZA Don',
            ];

            $response = $http->post($depositUrl, $payload);
            if (! $response->failed()) {
                $result = $response->json() ?? [];
                $result['depositId'] = $depositId;
                return $result;
            }

            $rawError = $response->body();
            Log::error('PAWAPAY RAW ERROR: ' . $rawError, [
                'http_status' => $response->status(),
                'deposit_id' => $depositId,
                'correspondent' => $correspondent,
                'amount' => $amount,
                'phone' => $phone,
            ]);

            $canTryNextProvider = $index < count($correspondents) - 1
                && str_contains(strtoupper($rawError), 'PROVIDER_NOT_FOUND');
            if (! $canTryNextProvider) {
                throw new RuntimeException('PawaPay a refusé la demande (' . $response->status() . '): ' . $rawError);
            }
        }

        throw new RuntimeException('Aucun provider PawaPay disponible.');
    }

    public static function providers(): array
    {
        return self::PROVIDERS;
    }
}
