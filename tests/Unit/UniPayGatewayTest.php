<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UniPayGatewayTest extends TestCase
{
    public function test_it_initiates_unipay_payment_with_api_key_header(): void
    {
        Http::fake([
            'https://unipay-api.onrender.com/v1/payment/initiate' => Http::response([
                'success' => true,
                'message' => 'Paiement initié.',
                'reference' => 'don_123',
                'payment_url' => 'https://payment.test/checkout/don_123',
            ], 200),
        ]);

        config()->set('services.unipay.api_key', 'up_72618c931ad3be0753277feeaecdda0c');
        config()->set('services.unipay.base_url', 'https://unipay-api.onrender.com');

        $gateway = new \App\Services\UniPayGateway();
        $result = $gateway->createPayment(
            2500,
            'don_123',
            '0970000000',
            'orange',
            'collect'
        );

        $this->assertTrue($result['success']);
        $this->assertSame('don_123', $result['reference']);

        Http::assertSent(function ($request) {
            $this->assertSame('https://unipay-api.onrender.com/v1/payment/initiate', $request->url());
            $this->assertSame('up_72618c931ad3be0753277feeaecdda0c', $request->header('X-API-Key')[0] ?? null);
            $this->assertSame('orange', data_get($request->data(), 'operator'));
            $this->assertSame('collect', data_get($request->data(), 'direction'));
            $this->assertSame('+243970000000', data_get($request->data(), 'phone'));

            return true;
        });
    }

    public function test_it_checks_unipay_payment_status_with_api_key_header(): void
    {
        Http::fake([
            'https://unipay-api.onrender.com/v1/payment/status/don_123' => Http::response([
                'success' => true,
                'status' => 'paid',
                'reference' => 'don_123',
            ], 200),
        ]);

        config()->set('services.unipay.api_key', 'up_72618c931ad3be0753277feeaecdda0c');
        config()->set('services.unipay.base_url', 'https://unipay-api.onrender.com');

        $gateway = new \App\Services\UniPayGateway();
        $result = $gateway->checkStatus('don_123');

        $this->assertTrue($result['success']);
        $this->assertSame('paid', strtolower((string) ($result['response']['status'] ?? '')));

        Http::assertSent(function ($request) {
            $this->assertSame('https://unipay-api.onrender.com/v1/payment/status/don_123', $request->url());
            $this->assertSame('up_72618c931ad3be0753277feeaecdda0c', $request->header('X-API-Key')[0] ?? null);

            return true;
        });
    }
}
