<?php

declare(strict_types=1);

use Joranski\Addressing\Data\AddressData;
use Joranski\Addressing\Data\AddressIssue;
use Joranski\Addressing\Data\VerificationResult;
use Joranski\Addressing\Enums\DeliverabilityVerdict;
use Joranski\Addressing\Enums\IssueCode;
use Joranski\Addressing\Enums\IssueSeverity;

it('builds an unverified result with no issues by default', function (): void {
    $a = new AddressData('US');
    $r = VerificationResult::unverified($a);

    expect($r->address)->toBe($a)
        ->and($r->verdict)->toBe(DeliverabilityVerdict::Unverified)
        ->and($r->isComplete)->toBeFalse()
        ->and($r->issues)->toBe([])
        ->and($r->hasIssues())->toBeFalse()
        ->and($r->isDeliverable())->toBeFalse()
        ->and($r->isError())->toBeFalse()
        ->and($r->fromCache)->toBeFalse();
});

it('builds an error result with an api_error / error severity issue', function (): void {
    $a = new AddressData('US');
    $r = VerificationResult::error($a, 'Google API timed out');

    expect($r->verdict)->toBe(DeliverabilityVerdict::Unverified)
        ->and($r->isError())->toBeTrue()
        ->and($r->isDeliverable())->toBeFalse()
        ->and($r->issues)->toHaveCount(1)
        ->and($r->issues[0])->toBeInstanceOf(AddressIssue::class)
        ->and($r->issues[0]->code)->toBe(IssueCode::ApiError)
        ->and($r->issues[0]->severity)->toBe(IssueSeverity::Error)
        ->and($r->issues[0]->message)->toBe('Google API timed out');
});

it('builds a deliverable result', function (): void {
    $a = new AddressData('US', addressLine1: '1 Main St');
    $r = VerificationResult::deliverable(
        address: $a,
        isComplete: true,
        isResidential: true,
        isBusiness: false,
        isPoBox: false,
        formattedAddress: '1 Main St, Phoenix, AZ 85001, USA',
        responseId: 'resp-123',
    );

    expect($r->verdict)->toBe(DeliverabilityVerdict::Deliverable)
        ->and($r->isDeliverable())->toBeTrue()
        ->and($r->isError())->toBeFalse()
        ->and($r->isComplete)->toBeTrue()
        ->and($r->isResidential)->toBeTrue()
        ->and($r->isBusiness)->toBeFalse()
        ->and($r->isPoBox)->toBeFalse()
        ->and($r->formattedAddress)->toBe('1 Main St, Phoenix, AZ 85001, USA')
        ->and($r->responseId)->toBe('resp-123');
});

it('round-trips through toArray + fromArray (critical for cache rehydration)', function (): void {
    $r = VerificationResult::deliverable(
        address: new AddressData('DE', addressLine1: 'Hauptstr 1', locality: 'Berlin', postalCode: '10115'),
        isComplete: true,
        isResidential: false,
        isBusiness: true,
        isPoBox: false,
        formattedAddress: 'Hauptstr 1, 10115 Berlin, Deutschland',
        responseId: 'resp-abc',
    );

    $rehydrated = VerificationResult::fromArray($r->toArray());

    expect($rehydrated)->toEqual($r);
});

it('round-trips an error result with its issues', function (): void {
    $r = VerificationResult::error(new AddressData('US'), 'Boom');
    $rehydrated = VerificationResult::fromArray($r->toArray());

    expect($rehydrated)->toEqual($r);
});

it('preserves raw payload in toArray + fromArray', function (): void {
    $r = new VerificationResult(
        address: new AddressData('US'),
        verdict: DeliverabilityVerdict::Deliverable,
        isComplete: true,
        isResidential: true,
        isBusiness: false,
        isPoBox: false,
        issues: [],
        responseId: 'r-1',
        formattedAddress: '1 Main',
        raw: ['result' => ['address' => ['formattedAddress' => '1 Main']]],
    );

    $rehydrated = VerificationResult::fromArray($r->toArray());

    expect($rehydrated->raw)->toBe($r->raw);
});

it('markAsCached() returns a new instance with fromCache=true; original untouched', function (): void {
    $a = new AddressData('US');
    $r = VerificationResult::unverified($a);
    $cached = $r->markAsCached();

    expect($r->fromCache)->toBeFalse()
        ->and($cached->fromCache)->toBeTrue()
        ->and($cached->address)->toBe($a)
        ->and($cached->verdict)->toBe($r->verdict);
});

it('issuesOfSeverity() filters by severity', function (): void {
    $err = new AddressIssue(IssueCode::PostalCodeFormat, IssueSeverity::Error, 'bad postal');
    $warn = new AddressIssue(IssueCode::UnconfirmedComponent, IssueSeverity::Warning, 'unconfirmed');
    $info = new AddressIssue(IssueCode::SuspiciousComponent, IssueSeverity::Info, 'looks weird');

    $r = VerificationResult::unverified(new AddressData('US'), [$err, $warn, $info]);

    expect($r->issuesOfSeverity(IssueSeverity::Error))->toBe([$err])
        ->and($r->issuesOfSeverity(IssueSeverity::Warning))->toBe([$warn])
        ->and($r->issuesOfSeverity(IssueSeverity::Info))->toBe([$info])
        ->and($r->hasIssues())->toBeTrue();
});

it('is readonly — mutation throws', function (): void {
    $r = VerificationResult::unverified(new AddressData('US'));

    expect(fn () => $r->fromCache = true)->toThrow(Error::class);
});
