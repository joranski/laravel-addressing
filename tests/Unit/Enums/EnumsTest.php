<?php

declare(strict_types=1);

use Joranski\Addressing\Enums\AddressUsage;
use Joranski\Addressing\Enums\DeliverabilityVerdict;
use Joranski\Addressing\Enums\IssueCode;
use Joranski\Addressing\Enums\IssueSeverity;

it('exposes the AddressUsage cases with stable string values', function (): void {
    expect(AddressUsage::cases())->toHaveCount(3)
        ->and(AddressUsage::ShippingDefault->value)->toBe('shipping_default')
        ->and(AddressUsage::BillingDefault->value)->toBe('billing_default')
        ->and(AddressUsage::Other->value)->toBe('other');
});

it('exposes the DeliverabilityVerdict cases with stable string values', function (): void {
    expect(DeliverabilityVerdict::Deliverable->value)->toBe('deliverable')
        ->and(DeliverabilityVerdict::Undeliverable->value)->toBe('undeliverable')
        ->and(DeliverabilityVerdict::Unverified->value)->toBe('unverified');
});

it('exposes IssueSeverity with Error / Warning / Info', function (): void {
    expect(IssueSeverity::cases())->toHaveCount(3)
        ->and(IssueSeverity::Error->value)->toBe('error')
        ->and(IssueSeverity::Warning->value)->toBe('warning')
        ->and(IssueSeverity::Info->value)->toBe('info');
});

it('exposes all 11 IssueCode cases needed by the verifier', function (): void {
    $expectedValues = [
        'missing_street_number',
        'missing_route',
        'unconfirmed_component',
        'suspicious_component',
        'ambiguous_suffix',
        'requires_subpremise',
        'granularity_too_low',
        'postal_code_format',
        'subdivision_invalid',
        'required_field_missing',
        'api_error',
    ];

    $actualValues = array_map(fn (IssueCode $c) => $c->value, IssueCode::cases());
    sort($actualValues);
    sort($expectedValues);

    expect(IssueCode::cases())->toHaveCount(11)
        ->and(IssueCode::PostalCodeFormat->value)->toBe('postal_code_format')
        ->and(IssueCode::ApiError->value)->toBe('api_error')
        ->and($actualValues)->toBe($expectedValues);
});
