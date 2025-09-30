<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case STRIPE_CHECKOUT = 'stripe_checkout';
    case VISA = 'visa';
    case CREDIT = 'credit';
    case CASH = 'cash';
    case BANK_TRANSFER = 'bank_transfer';
    case DEBIT_CARD = 'debit_card';
    case PAYPAL = 'paypal';
    case APPLE_PAY = 'apple_pay';
    case GOOGLE_PAY = 'google_pay';

    /**
     * Get all enum values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get display label for the payment method
     */
    public function label(): string
    {
        return match ($this) {
            self::STRIPE_CHECKOUT => __('enums.payment_method.stripe_checkout'),
            self::VISA => __('enums.payment_method.visa'),
            self::CREDIT => __('enums.payment_method.credit_card'),
            self::CASH => __('enums.payment_method.cash'),
            self::BANK_TRANSFER => __('enums.payment_method.Bank Transfer'),
            self::DEBIT_CARD => __('enums.payment_method.debit_card'),
            self::PAYPAL => __('enums.payment_method.paypal'),
            self::APPLE_PAY => __('enums.payment_method.apple_pay'),
            self::GOOGLE_PAY => __('enums.payment_method.google_pay'),
        };
    }

    /**
     * Check if payment method is digital
     */
    public function isDigital(): bool
    {
        return match ($this) {
            self::CASH => false,
            default => true,
        };
    }

    /**
     * Get icon for payment method
     */
    public function icon(): string
    {
        return match ($this) {
            self::STRIPE_CHECKOUT => 'credit-card',
            self::VISA => 'credit-card',
            self::CREDIT => 'credit-card',
            self::CASH => 'banknotes',
            self::BANK_TRANSFER => 'building-library',
            self::DEBIT_CARD => 'credit-card',
            self::PAYPAL => 'globe-alt',
            self::APPLE_PAY => 'device-phone-mobile',
            self::GOOGLE_PAY => 'device-phone-mobile',
        };
    }
}
