<?php

declare(strict_types=1);

namespace Joranski\Addressing\Filament\Concerns;

use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Joranski\Addressing\Models\Address;
use Joranski\Addressing\Support\AddressAuthorization;

/**
 * Routes Filament relation-manager authorization for Address rows through
 * {@see AddressAuthorization} so Shield policies and fallback rules behave consistently.
 */
trait AuthorizesAddressRecords
{
    public function getAuthorizationResponse(string $action, ?Model $record = null): Response
    {
        if (! $this->managesAddressRecords()) {
            return parent::getAuthorizationResponse($action, $record);
        }

        if (static::shouldSkipAuthorization()) {
            return Response::allow();
        }

        return AddressAuthorization::toResponse(action: $action, address: $record);
    }

    protected function managesAddressRecords(): bool
    {
        $model = $this->getTable()->getModel();

        if (is_string($model)) {
            return is_a($model, Address::class, true);
        }

        return $model instanceof Address;
    }
}
