<?php

declare(strict_types=1);

namespace Joranski\Addressing\Filament\Concerns;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Joranski\Addressing\Filament\Forms\Components\AddressInput;

trait ConfiguresAddressRelationManagerActions
{
    protected function configureAddressCreateAction(CreateAction $action): CreateAction
    {
        return $action
            ->mutateDataUsing(AddressInput::mutateDataUsing())
            ->modalWidth(width: '7xl')
            ->stickyModalHeader()
            ->stickyModalFooter();
    }

    protected function configureAddressEditAction(EditAction $action): EditAction
    {
        return $action
            ->mutateDataUsing(AddressInput::mutateDataUsing())
            ->modalWidth(width: '7xl')
            ->stickyModalHeader()
            ->stickyModalFooter();
    }
}
