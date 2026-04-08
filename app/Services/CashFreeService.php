<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CashfreeService
{
    public function pgHeaders(): array
    {
        return [
            'x-client-id'     => config('services.cashfree.pg.client_id'),
            'x-client-secret' => config('services.cashfree.pg.secret_key'),
            'x-api-version'   => config('services.cashfree.pg.api_version', '2023-08-01'),
            'accept'          => 'application/json',
            'content-type'    => 'application/json',
        ];
    }

    public function payoutHeaders(): array
    {
        return [
            'x-client-id'     => config('services.cashfree.payout.client_id'),
            'x-client-secret' => config('services.cashfree.payout.secret_key'),
            'accept'          => 'application/json',
            'content-type'    => 'application/json',
        ];
    }

    public function pgBaseUrl(): string
    {
        return rtrim(config('services.cashfree.pg.base_url'), '/');
    }

    public function payoutBaseUrl(): string
    {
        return rtrim(config('services.cashfree.payout.base_url'), '/');
    }

    public function createOrder(array $payload): array
    {
        $response = Http::withHeaders($this->pgHeaders())
            ->post($this->pgBaseUrl() . '/orders', $payload);

        if (! $response->successful()) {
            throw new \Exception('Cashfree create order failed: ' . $response->body());
        }

        return $response->json();
    }

    public function getOrder(string $cashfreeOrderId): array
    {
        $response = Http::withHeaders($this->pgHeaders())
            ->get($this->pgBaseUrl() . '/orders/' . $cashfreeOrderId);

        if (! $response->successful()) {
            throw new \Exception('Cashfree get order failed: ' . $response->body());
        }

        return $response->json();
    }

    public function getOrderPayments(string $cashfreeOrderId): array
    {
        $response = Http::withHeaders($this->pgHeaders())
            ->get($this->pgBaseUrl() . '/orders/' . $cashfreeOrderId . '/payments');

        if (! $response->successful()) {
            throw new \Exception('Cashfree get order payments failed: ' . $response->body());
        }

        return $response->json();
    }

    public function verifyWebhookSignature(string $rawBody, ?string $timestamp, ?string $signature): bool
    {
        if (! $timestamp || ! $signature) {
            return false;
        }

        $secret = config('services.cashfree.pg.secret_key');
        $signedPayload = $timestamp . $rawBody;
        $generatedSignature = base64_encode(hash_hmac('sha256', $signedPayload, $secret, true));

        return hash_equals($generatedSignature, $signature);
    }

    public function createBeneficiary(array $payload): array
    {
        $response = Http::withHeaders($this->payoutHeaders())
            ->post($this->payoutBaseUrl() . '/beneficiary', $payload);

        if (! $response->successful()) {
            throw new \Exception('Cashfree create beneficiary failed: ' . $response->body());
        }

        return $response->json();
    }

    public function getBeneficiary(string $beneficiaryId): array
    {
        $response = Http::withHeaders($this->payoutHeaders())
            ->get($this->payoutBaseUrl() . '/beneficiary', [
                'beneficiary_id' => $beneficiaryId,
            ]);

        if (! $response->successful()) {
            throw new \Exception('Cashfree get beneficiary failed: ' . $response->body());
        }

        return $response->json();
    }

    public function createOrGetBeneficiary(array $payload): array
    {
        try {
            return $this->createBeneficiary($payload);
        } catch (\Throwable $e) {
            return $this->getBeneficiary($payload['beneficiary_id']);
        }
    }

    public function createTransfer(array $payload): array
    {
        $response = Http::withHeaders($this->payoutHeaders())
            ->post($this->payoutBaseUrl() . '/transfers', $payload);

        if (! $response->successful()) {
            throw new \Exception('Cashfree transfer failed: ' . $response->body());
        }

        return $response->json();
    }

    public function getTransferStatus(string $transferId): array
    {
        $response = Http::withHeaders($this->payoutHeaders())
            ->get($this->payoutBaseUrl() . '/transfers/' . $transferId);

        if (! $response->successful()) {
            throw new \Exception('Cashfree transfer status failed: ' . $response->body());
        }

        return $response->json();
    }
}