<?php

declare(strict_types=1);

use Joranski\Addressing\Data\AddressData;
use Joranski\Addressing\Enums\IssueCode;
use Joranski\Addressing\Enums\IssueSeverity;
use Joranski\Addressing\Verifiers\GoogleAddressVerifier;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Http::preventStrayRequests();
});

it('returns an unverified result with an ApiError issue when api key is missing', function (): void {
    config()->set('addressing.google.api_key', null);

    $result = (new GoogleAddressVerifier)->verify(
        new AddressData(countryCode: 'US', addressLine1: '1 Main St')
    );

    expect($result->verdict->value)->toBe('unverified')
        ->and($result->isError())->toBeTrue()
        ->and($result->issues[0]->code)->toBe(IssueCode::ApiError)
        ->and(Http::recorded())->toBeEmpty();
});

it('posts to Google and maps a successful response into a Deliverable VerificationResult', function (): void {
    config()->set('addressing.google.api_key', 'fake-key');

    $fixture = json_decode(
        file_get_contents(__DIR__.'/../../Fixtures/verified-us-residential.json'),
        associative: true,
    );
    Http::fake(['addressvalidation.googleapis.com/*' => Http::response($fixture)]);

    $result = (new GoogleAddressVerifier)->verify(new AddressData(
        countryCode: 'US',
        addressLine1: '1 Main St',
        locality: 'Phoenix',
        administrativeArea: 'AZ',
        postalCode: '85001',
    ));

    expect($result->verdict->value)->toBe('deliverable')
        ->and($result->isComplete)->toBeTrue()
        ->and($result->isResidential)->toBeTrue()
        ->and($result->isBusiness)->toBeFalse()
        ->and($result->isPoBox)->toBeFalse()
        ->and($result->responseId)->toBe('verified-us-residential-fixture')
        ->and($result->formattedAddress)->toBe('1 Main St, Phoenix, AZ 85001, USA')
        ->and($result->address->latitude)->toBe(33.4484)
        ->and($result->address->longitude)->toBe(-112.074)
        ->and($result->raw)->not->toBeEmpty();
});

it('detects business addresses from the metadata', function (): void {
    config()->set('addressing.google.api_key', 'fake-key');
    $fixture = json_decode(
        file_get_contents(__DIR__.'/../../Fixtures/verified-us-business.json'),
        true,
    );
    Http::fake(['addressvalidation.googleapis.com/*' => Http::response($fixture)]);

    $result = (new GoogleAddressVerifier)->verify(new AddressData(
        countryCode: 'US',
        addressLine1: '1600 Amphitheatre Pkwy',
        locality: 'Mountain View',
        administrativeArea: 'CA',
        postalCode: '94043',
    ));

    expect($result->isBusiness)->toBeTrue()
        ->and($result->isResidential)->toBeFalse();
});

it('surfaces missing-subpremise issues from incomplete responses', function (): void {
    config()->set('addressing.google.api_key', 'fake-key');
    $fixture = json_decode(
        file_get_contents(__DIR__.'/../../Fixtures/incomplete-missing-subpremise.json'),
        true,
    );
    Http::fake(['addressvalidation.googleapis.com/*' => Http::response($fixture)]);

    $result = (new GoogleAddressVerifier)->verify(new AddressData(
        countryCode: 'US',
        addressLine1: '100 Apartment Way',
        locality: 'San Francisco',
        administrativeArea: 'CA',
        postalCode: '94110',
    ));

    $codes = array_map(fn ($i) => $i->code, $result->issues);

    expect($result->isComplete)->toBeFalse()
        ->and($codes)->toContain(IssueCode::RequiresSubpremise);
});

it('returns an error result on HTTP failure (does not throw — cached decorator relies on never throwing)', function (): void {
    config()->set('addressing.google.api_key', 'fake-key');
    Http::fake(['addressvalidation.googleapis.com/*' => Http::response(['error' => 'bad'], 400)]);

    $result = (new GoogleAddressVerifier)->verify(
        new AddressData(countryCode: 'US', addressLine1: '1 Main St')
    );

    expect($result->isError())->toBeTrue()
        ->and($result->issues[0]->code)->toBe(IssueCode::ApiError)
        ->and($result->issues[0]->severity)->toBe(IssueSeverity::Error);
});

it('has id "google"', function (): void {
    expect((new GoogleAddressVerifier)->id())->toBe('google');
});

it('enables USPS CASS for US but not for other countries', function (): void {
    config()->set('addressing.google.api_key', 'fake-key');
    Http::fake(['addressvalidation.googleapis.com/*' => Http::response([])]);

    (new GoogleAddressVerifier)->verify(new AddressData(countryCode: 'DE', addressLine1: 'Hauptstr 1'));

    Http::assertSent(fn ($req) => ($req->data()['enableUspsCass'] ?? null) === false);
});

it('enables USPS CASS for US addresses', function (): void {
    config()->set('addressing.google.api_key', 'fake-key');
    Http::fake(['addressvalidation.googleapis.com/*' => Http::response([])]);

    (new GoogleAddressVerifier)->verify(new AddressData(countryCode: 'US', addressLine1: '1 Main St'));

    Http::assertSent(fn ($req) => ($req->data()['enableUspsCass'] ?? null) === true);
});

it('sends the API key in the URL query string', function (): void {
    config()->set('addressing.google.api_key', 'fake-key-xyz');
    Http::fake(['addressvalidation.googleapis.com/*' => Http::response([])]);

    (new GoogleAddressVerifier)->verify(new AddressData(countryCode: 'US', addressLine1: '1 Main St'));

    Http::assertSent(fn ($req) => str_contains($req->url(), 'key=fake-key-xyz'));
});
