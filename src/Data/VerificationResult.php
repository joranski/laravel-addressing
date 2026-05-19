<?php

declare(strict_types=1);

namespace Joranski\Addressing\Data;

// @package-candidate score=6/6 target-package=joranski/laravel-addressing
// Target extraction path: /home/joranski/packages/laravel-addressing

use Joranski\Addressing\Enums\DeliverabilityVerdict;
use Joranski\Addressing\Enums\IssueCode;
use Joranski\Addressing\Enums\IssueSeverity;

/**
 * Outcome of an AddressVerifier::verify() call.
 *
 * Immutable. Always returned (verifiers never throw — use ::error() for
 * upstream failures). Serializable via toArray/fromArray so CachedVerifier
 * can persist and rehydrate without losing structured data.
 *
 * @phpstan-type RawResponse array<string, mixed>
 */
final readonly class VerificationResult
{
    /**
     * @param  list<AddressIssue>  $issues
     * @param  RawResponse  $raw
     */
    public function __construct(
        public AddressData $address,
        public DeliverabilityVerdict $verdict,
        public bool $isComplete,
        public bool $isResidential,
        public bool $isBusiness,
        public bool $isPoBox,
        public array $issues,
        public ?string $responseId = null,
        public ?string $formattedAddress = null,
        public array $raw = [],
        public bool $fromCache = false,
    ) {}

    /**
     * @param  list<AddressIssue>  $issues
     */
    public static function unverified(AddressData $address, array $issues = []): self
    {
        return new self(
            address: $address,
            verdict: DeliverabilityVerdict::Unverified,
            isComplete: false,
            isResidential: false,
            isBusiness: false,
            isPoBox: false,
            issues: array_values($issues),
        );
    }

    /**
     * Build a result representing an upstream/API failure.
     *
     * The single attached AddressIssue uses IssueCode::ApiError + Error severity
     * so the CachedVerifier knows not to cache it.
     */
    public static function error(AddressData $address, string $message): self
    {
        return new self(
            address: $address,
            verdict: DeliverabilityVerdict::Unverified,
            isComplete: false,
            isResidential: false,
            isBusiness: false,
            isPoBox: false,
            issues: [
                new AddressIssue(
                    code: IssueCode::ApiError,
                    severity: IssueSeverity::Error,
                    message: $message,
                ),
            ],
        );
    }

    /**
     * @param  list<AddressIssue>  $issues
     * @param  RawResponse  $raw
     */
    public static function deliverable(
        AddressData $address,
        bool $isComplete = true,
        bool $isResidential = false,
        bool $isBusiness = false,
        bool $isPoBox = false,
        array $issues = [],
        ?string $responseId = null,
        ?string $formattedAddress = null,
        array $raw = [],
    ): self {
        return new self(
            address: $address,
            verdict: DeliverabilityVerdict::Deliverable,
            isComplete: $isComplete,
            isResidential: $isResidential,
            isBusiness: $isBusiness,
            isPoBox: $isPoBox,
            issues: array_values($issues),
            responseId: $responseId,
            formattedAddress: $formattedAddress,
            raw: $raw,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'address' => $this->address->toArray(),
            'verdict' => $this->verdict->value,
            'isComplete' => $this->isComplete,
            'isResidential' => $this->isResidential,
            'isBusiness' => $this->isBusiness,
            'isPoBox' => $this->isPoBox,
            'issues' => array_map(
                static fn (AddressIssue $i): array => [
                    'code' => $i->code->value,
                    'severity' => $i->severity->value,
                    'message' => $i->message,
                    'field' => $i->field,
                    'context' => $i->context,
                ],
                $this->issues,
            ),
            'responseId' => $this->responseId,
            'formattedAddress' => $this->formattedAddress,
            'raw' => $this->raw,
            'fromCache' => $this->fromCache,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            address: AddressData::fromArray($data['address'] ?? []),
            verdict: DeliverabilityVerdict::from((string) ($data['verdict'] ?? 'unverified')),
            isComplete: (bool) ($data['isComplete'] ?? false),
            isResidential: (bool) ($data['isResidential'] ?? false),
            isBusiness: (bool) ($data['isBusiness'] ?? false),
            isPoBox: (bool) ($data['isPoBox'] ?? false),
            issues: array_map(
                static fn (array $i): AddressIssue => new AddressIssue(
                    code: IssueCode::from((string) $i['code']),
                    severity: IssueSeverity::from((string) $i['severity']),
                    message: (string) $i['message'],
                    field: $i['field'] ?? null,
                    context: (array) ($i['context'] ?? []),
                ),
                array_values((array) ($data['issues'] ?? [])),
            ),
            responseId: $data['responseId'] ?? null,
            formattedAddress: $data['formattedAddress'] ?? null,
            raw: (array) ($data['raw'] ?? []),
            fromCache: (bool) ($data['fromCache'] ?? false),
        );
    }

    public function markAsCached(): self
    {
        return new self(
            address: $this->address,
            verdict: $this->verdict,
            isComplete: $this->isComplete,
            isResidential: $this->isResidential,
            isBusiness: $this->isBusiness,
            isPoBox: $this->isPoBox,
            issues: $this->issues,
            responseId: $this->responseId,
            formattedAddress: $this->formattedAddress,
            raw: $this->raw,
            fromCache: true,
        );
    }

    public function isDeliverable(): bool
    {
        return $this->verdict === DeliverabilityVerdict::Deliverable;
    }

    /**
     * True if any issue has IssueCode::ApiError (upstream failure).
     */
    public function isError(): bool
    {
        foreach ($this->issues as $issue) {
            if ($issue->code === IssueCode::ApiError) {
                return true;
            }
        }

        return false;
    }

    public function hasIssues(): bool
    {
        return $this->issues !== [];
    }

    /**
     * @return list<AddressIssue>
     */
    public function issuesOfSeverity(IssueSeverity $severity): array
    {
        return array_values(array_filter(
            $this->issues,
            static fn (AddressIssue $i): bool => $i->severity === $severity,
        ));
    }
}
