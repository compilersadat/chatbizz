<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CashFreeService
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

    public function ppiHeaders(): array
    {
        return [
            'x-client-id'     => config('services.cashfree.ppi.client_id'),
            'x-client-secret' => config('services.cashfree.ppi.secret_key'),
            'x-api-version'   => config('services.cashfree.ppi.api_version', '2025-11-01'),
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

    public function ppiBaseUrl(): string
    {
        return rtrim(config('services.cashfree.ppi.base_url'), '/');
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
        $ppiPayload = $this->mapToPpiBeneficiaryPayload($payload);

        $this->createOrGetPpiUser($ppiPayload['user']);

        $response = Http::withHeaders($this->ppiHeaders())
            ->post($this->ppiBaseUrl() . '/user/bene', $ppiPayload['beneficiary']);

        if (! $response->successful()) {
            throw new \Exception('Cashfree create beneficiary failed: ' . $response->body());
        }

        return $this->normalizePpiBeneficiaryResponse($response->json(), $payload);
    }

    public function getBeneficiary(string $beneficiaryId, ?string $userId = null): array
    {
        if (! $userId) {
            throw new \InvalidArgumentException('Cashfree PPI beneficiary lookup requires user_id.');
        }

        $response = Http::withHeaders($this->ppiHeaders())
            ->post($this->ppiBaseUrl() . '/user/bene/details', [
                'user_id' => $userId,
                'bene_id' => $beneficiaryId,
            ]);

        if (! $response->successful()) {
            throw new \Exception('Cashfree get beneficiary failed: ' . $response->body());
        }

        return $this->normalizePpiBeneficiaryResponse($response->json(), [
            'beneficiary_id' => $beneficiaryId,
            'user_id' => $userId,
        ]);
    }

    public function createOrGetBeneficiary(array $payload): array
    {
        try {
            return $this->createBeneficiary($payload);
        } catch (\Throwable $e) {
            return $this->getBeneficiary(
                $payload['beneficiary_id'],
                $payload['user_id'] ?? null
            );
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

    protected function createOrGetPpiUser(array $user): array
    {
        try {
            return $this->createPpiUser($user);
        } catch (\Throwable $e) {
            return $this->getPpiUser($user['user_id']);
        }
    }

    protected function createPpiUser(array $user): array
    {
        $response = Http::withHeaders($this->ppiHeaders())
            ->post($this->ppiBaseUrl() . '/user', $user);

        if (! $response->successful()) {
            throw new \Exception('Cashfree create PPI user failed: ' . $response->body());
        }

        return $response->json();
    }

    protected function getPpiUser(string $userId): array
    {
        $response = Http::withHeaders($this->ppiHeaders())
            ->post($this->ppiBaseUrl() . '/user/details', [
                'user_id' => $userId,
            ]);

        if (! $response->successful()) {
            throw new \Exception('Cashfree get PPI user failed: ' . $response->body());
        }

        return $response->json();
    }

    protected function mapToPpiBeneficiaryPayload(array $payload): array
    {
        $userId = $payload['user_id'] ?? $payload['beneficiary_id'];
        $fullName = trim((string) ($payload['beneficiary_name'] ?? 'Beneficiary'));
        [$firstName, $lastName] = $this->splitName($fullName);

        $email = data_get($payload, 'beneficiary_contact_details.beneficiary_email')
            ?? (strtolower($userId) . '@example.com');
        $phone = preg_replace('/\D+/', '', (string) data_get($payload, 'beneficiary_contact_details.beneficiary_phone', '9999999999'));
        $phone = substr($phone ?: '9999999999', -10);

        $instrumentDetails = (array) ($payload['beneficiary_instrument_details'] ?? []);
        $beneInstrument = [
            'bene_instrument_id' => $payload['bene_instrument_id']
                ?? $payload['beneficiary_id'] . '_' . (! empty($instrumentDetails['vpa']) ? 'upi' : 'bank'),
            'instrument_type' => ! empty($instrumentDetails['vpa']) ? 'UPI' : 'BANK_ACCOUNT',
        ];

        if (! empty($instrumentDetails['vpa'])) {
            $beneInstrument['vpa'] = $instrumentDetails['vpa'];
        } else {
            $beneInstrument['bank_account_number'] = $instrumentDetails['bank_account_number'] ?? '';
            $beneInstrument['bank_ifsc'] = $instrumentDetails['bank_ifsc'] ?? '';
        }

        return [
            'user' => [
                'user_id' => $userId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phone,
                'country_code' => '+91',
            ],
            'beneficiary' => [
                'user_id' => $userId,
                'bene_id' => $payload['beneficiary_id'],
                'bene_first_name' => $firstName,
                'bene_last_name' => $lastName,
                'bene_instruments' => [$beneInstrument],
                'phone' => $phone,
                'email' => $email,
            ],
        ];
    }

    protected function normalizePpiBeneficiaryResponse(array $response, array $originalPayload): array
    {
        $instrument = data_get($response, 'bene_instruments.0', []);

        return [
            'beneficiary_id' => $response['bene_id'] ?? $originalPayload['beneficiary_id'],
            'user_id' => $response['user_id'] ?? ($originalPayload['user_id'] ?? null),
            'beneficiary_name' => trim(implode(' ', array_filter([
                $response['bene_first_name'] ?? null,
                $response['bene_last_name'] ?? null,
            ]))) ?: ($originalPayload['beneficiary_name'] ?? null),
            'beneficiary_instrument_details' => [
                'bank_account_number' => $instrument['bank_account_number'] ?? null,
                'bank_ifsc' => $instrument['bank_ifsc'] ?? null,
                'vpa' => $instrument['vpa'] ?? null,
            ],
            'raw' => $response,
        ];
    }

    protected function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName), 2) ?: [];
        $firstName = $parts[0] ?? 'Beneficiary';
        $lastName = $parts[1] ?? 'User';

        return [$firstName, $lastName];
    }
}
