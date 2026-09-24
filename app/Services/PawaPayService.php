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

        $correspondent = match ($provider) {
            'VODACOM', 'MPESA_COD' => 'MPESA_COD',
            'AIRTEL', 'AIRTEL_COD' => 'AIRTEL_COD',
            'ORANGE', 'ORANGE_COD' => 'ORANGE_COD',
            'AFRICELL', 'AFRICELL_COD' => 'AFRICELL_COD',
        };

        $apiKey = (string) config('services.pawapay.api_key');
        if ($apiKey === '') {
            Log::error('PawaPay API key missing on Render', [
                'env_present' => getenv('PAWAPAY_API_KEY') !== false,
                'config_present' => (bool) config('services.pawapay.api_key'),
            ]);
            throw new RuntimeException('Clé API manquante sur Render');
        }

        $payload = [
            'depositId' => $depositId,
            'amount' => (string) $amount,
            'currency' => 'CDF',
            'country' => 'COD',
            'correspondent' => $correspondent,
            'payer' => [
                'type' => 'MSISDN',
                'address' => ['value' => $phone],
            ],
            'customerTimestamp' => now()->utc()->format('Y-m-d\\TH:i:s\\Z'),
            'statementDescription' => 'CONZA',
        ];

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.pawapay.timeout', 20))
            ->post(rtrim((string) config('services.pawapay.base_url'), '/') . '/v2/deposits', $payload);

        if ($response->failed()) {
            $rawError = $response->body();
            Log::error('PAWAPAY RAW ERROR: ' . $rawError, [
                'http_status' => $response->status(),
                'deposit_id' => $depositId,
                'correspondent' => $correspondent,
                'amount' => $amount,
                'phone' => $phone,
            ]);

            if ($response->status() === 400 && $correspondent === 'MPESA_COD') {
                $depositId = 'CONZA-' . now()->valueOf() . '-' . Str::lower(Str::random(8));
                $payload['depositId'] = $depositId;
                $payload['correspondent'] = 'MPESA';

                $response = Http::withToken($apiKey)
                    ->acceptJson()
                    ->asJson()
                    ->timeout((int) config('services.pawapay.timeout', 20))
                    ->post(rtrim((string) config('services.pawapay.base_url'), '/') . '/v2/deposits', $payload);

                if (! $response->failed()) {
                    $result = $response->json() ?? [];
                    $result['depositId'] = $depositId;
                    return $result;
                }

                $rawError = $response->body();
                Log::error('PAWAPAY RAW ERROR: ' . $rawError, [
                    'http_status' => $response->status(),
                    'deposit_id' => $depositId,
                    'correspondent' => 'MPESA',
                    'amount' => $amount,
                    'phone' => $phone,
                ]);
            }

            throw new RuntimeException('PawaPay a refusé la demande (' . $response->status() . '): ' . $rawError);
        }

        return $response->json() ?? [];
    }

    public static function providers(): array
    {
        return self::PROVIDERS;
    }
}
