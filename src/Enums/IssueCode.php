<?php

declare(strict_types=1);

namespace Joranski\Addressing\Enums;

// @package-candidate score=6/6 target-package=joranski/laravel-addressing
// Target extraction path: /home/joranski/packages/laravel-addressing

enum IssueCode: string
{
    case MissingStreetNumber = 'missing_street_number';
    case MissingRoute = 'missing_route';
    case UnconfirmedComponent = 'unconfirmed_component';
    case SuspiciousComponent = 'suspicious_component';
    case AmbiguousSuffix = 'ambiguous_suffix';
    case RequiresSubpremise = 'requires_subpremise';
    case GranularityTooLow = 'granularity_too_low';
    case PostalCodeFormat = 'postal_code_format';
    case SubdivisionInvalid = 'subdivision_invalid';
    case RequiredFieldMissing = 'required_field_missing';
    case ApiError = 'api_error';
}
