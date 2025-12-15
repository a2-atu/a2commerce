<?php

namespace App\Services\A2\Commerce;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayPalPaymentService
{
    protected string $clientId;
    protected string $secret;
    protected string $mode;
    protected string $baseUrl;

    public function __construct()
    {
        $this->clientId = config('a2_commerce.paypal.client_id', env('A2_PAYPAL_CLIENT_ID'));
        $this->secret = config('a2_commerce.paypal.secret', env('A2_PAYPAL_SECRET'));
        $this->mode = config('a2_commerce.paypal.mode', env('A2_PAYPAL_MODE', 'sandbox'));
        $this->baseUrl = $this->mode === 'sandbox'
            ? 'https://api.sandbox.paypal.com'
            : 'https://api.paypal.com';
    }

    /**
     * Get access token
     */
    protected function getAccessToken(): ?string
    {
        try {
            $response = Http::asForm()->withBasicAuth($this->clientId, $this->secret)
                ->post("{$this->baseUrl}/v1/oauth2/token", [
                    'grant_type' => 'client_credentials',
                ]);

            if ($response->successful()) {
                return $response->json('access_token');
            }

            Log::error('PayPal access token failed', ['response' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('PayPal access token error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Verify PayPal order
     */
    public function verifyOrder(string $orderId): array
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'error' => 'Failed to get access token'];
        }

        try {
            $response = Http::withToken($accessToken)
                ->get("{$this->baseUrl}/v2/checkout/orders/{$orderId}");

            if ($response->successful()) {
                $orderData = $response->json();
                $status = $orderData['status'] ?? null;

                return [
                    'success' => $status === 'COMPLETED',
                    'status' => $status,
                    'order_id' => $orderId,
                    'data' => $orderData,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('message', 'Verification failed'),
                'data' => $response->json(),
            ];
        } catch (\Exception $e) {
            Log::error('PayPal order verification error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Capture PayPal payment
     */
    public function capturePayment(string $orderId): array
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'error' => 'Failed to get access token'];
        }

        try {
            $response = Http::withToken($accessToken)
                ->post("{$this->baseUrl}/v2/checkout/orders/{$orderId}/capture");

            if ($response->successful()) {
                $data = $response->json();
                $status = $data['status'] ?? null;

                return [
                    'success' => $status === 'COMPLETED',
                    'status' => $status,
                    'order_id' => $orderId,
                    'transaction_id' => $data['purchase_units'][0]['payments']['captures'][0]['id'] ?? null,
                    'data' => $data,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('message', 'Capture failed'),
                'data' => $response->json(),
            ];
        } catch (\Exception $e) {
            Log::error('PayPal payment capture error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
