<?php

namespace App\Services\Payments;

use App\Models\Plan;
use App\Models\Subscription;
use RuntimeException;

class SePayPaymentService
{
    public function expectedAmountVnd(Plan $plan): int
    {
        $exchangeRate = (float) env('SEPAY_VND_EXCHANGE_RATE', 25000);

        return max(1000, (int) round(((float) $plan->price) * $exchangeRate));
    }

    public function paymentReference(Subscription $subscription): string
    {
        return sprintf('MONO-SUB-%d', $subscription->id);
    }

    /**
     * @return array<string, mixed>
     */
    public function createCheckout(Subscription $subscription, Plan $plan): array
    {
        $accountNumber = env('SEPAY_ACCOUNT_NUMBER');
        $accountName = env('SEPAY_ACCOUNT_NAME');
        $bankName = env('SEPAY_BANK_NAME');

        if (!$accountNumber || !$accountName || !$bankName) {
            throw new RuntimeException('SePay bank account configuration is incomplete.');
        }

        $amount = $this->expectedAmountVnd($plan);
        $reference = $this->paymentReference($subscription);

        return [
            'provider' => PaymentCheckoutService::PROVIDER_SEPAY,
            'mode' => 'qr',
            'subscriptionId' => $subscription->id,
            'transactionId' => $reference,
            'payment' => [
                'bankName' => $bankName,
                'accountName' => $accountName,
                'accountNumber' => $accountNumber,
                'amount' => $amount,
                'currency' => 'VND',
                'content' => $reference,
                'qrCode' => $this->generateQrCode($accountNumber, $bankName, $amount, $reference),
                'instructions' => [
                    'Open your banking app or VietQR scanner.',
                    'Scan the QR code or copy the bank transfer information.',
                    'Transfer the exact amount shown.',
                    'Keep the transfer content exactly as provided for automatic confirmation.',
                ],
            ],
        ];
    }

    public function verifyWebhookAuthorization(?string $authorizationHeader): bool
    {
        $expectedKey = env('SEPAY_WEBHOOK_API_KEY');

        if (!$expectedKey || !$authorizationHeader) {
            return false;
        }

        $providedKey = null;

        if (str_starts_with($authorizationHeader, 'Apikey ')) {
            $providedKey = substr($authorizationHeader, 7);
        } elseif (str_starts_with($authorizationHeader, 'Bearer ')) {
            $providedKey = substr($authorizationHeader, 7);
        }

        if (!$providedKey) {
            return false;
        }

        return hash_equals($expectedKey, $providedKey);
    }

    public function extractSubscriptionIdFromContent(?string $content): ?int
    {
        if (!$content) {
            return null;
        }

        if (preg_match('/MONO[-\s]?SUB[-\s]?(\d+)/i', $content, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }

    private function generateQrCode(string $accountNumber, string $bankName, int $amount, string $content): string
    {
        return 'https://qr.sepay.vn/img?' . http_build_query([
            'acc' => $accountNumber,
            'bank' => $bankName,
            'amount' => $amount,
            'des' => $content,
            'template' => 'compact',
        ]);
    }
}
