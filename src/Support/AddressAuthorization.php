<?php

declare(strict_types=1);

namespace Joranski\Addressing\Support;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

final class AddressAuthorization
{
    public static function canViewAny(?Authenticatable $user = null): bool
    {
        $user ??= auth()->user();

        if ($user === null) {
            return false;
        }

        if (self::usesPolicyChecks()) {
            return $user->can('viewAny', AddressModels::addressClass());
        }

        return (bool) config('addressing.authorization.fallback.view_any', true);
    }

    public static function canView(Model $address, ?Authenticatable $user = null): bool
    {
        $user ??= auth()->user();

        if ($user === null) {
            return false;
        }

        if (! self::canViewAny($user)) {
            return false;
        }

        if (self::usesPolicyChecks()) {
            return $user->can('view', $address);
        }

        return (bool) config('addressing.authorization.fallback.view', true);
    }

    public static function canCreate(?Authenticatable $user = null): bool
    {
        $user ??= auth()->user();

        if ($user === null) {
            return false;
        }

        if (self::usesPolicyChecks()) {
            return $user->can('create', AddressModels::addressClass());
        }

        return (bool) config('addressing.authorization.fallback.create', true);
    }

    public static function canUpdate(Model $address, ?Authenticatable $user = null): bool
    {
        $user ??= auth()->user();

        if ($user === null) {
            return false;
        }

        if (self::usesPolicyChecks()) {
            return $user->can('update', $address);
        }

        return (bool) config('addressing.authorization.fallback.update', true);
    }

    public static function canDelete(Model $address, ?Authenticatable $user = null): bool
    {
        $user ??= auth()->user();

        if ($user === null) {
            return false;
        }

        if (self::usesPolicyChecks()) {
            if ($user->can('deleteAny', AddressModels::addressClass())) {
                return true;
            }

            return $user->can('delete', $address);
        }

        if ((bool) config('addressing.authorization.fallback.delete_any', false)) {
            return true;
        }

        return (bool) config('addressing.authorization.fallback.delete', true);
    }

    public static function canDeleteAny(?Authenticatable $user = null): bool
    {
        $user ??= auth()->user();

        if ($user === null) {
            return false;
        }

        if (self::usesPolicyChecks()) {
            return $user->can('deleteAny', AddressModels::addressClass());
        }

        return (bool) config('addressing.authorization.fallback.delete_any', false);
    }

    public static function canRestore(Model $address, ?Authenticatable $user = null): bool
    {
        return self::checkPolicyAbility(ability: 'restore', address: $address, user: $user, fallbackKey: 'restore');
    }

    public static function canForceDelete(Model $address, ?Authenticatable $user = null): bool
    {
        return self::checkPolicyAbility(ability: 'forceDelete', address: $address, user: $user, fallbackKey: 'force_delete');
    }

    public static function canForceDeleteAny(?Authenticatable $user = null): bool
    {
        return self::checkPolicyAnyAbility(ability: 'forceDeleteAny', user: $user, fallbackKey: 'force_delete_any');
    }

    public static function canRestoreAny(?Authenticatable $user = null): bool
    {
        return self::checkPolicyAnyAbility(ability: 'restoreAny', user: $user, fallbackKey: 'restore_any');
    }

    public static function canReplicate(Model $address, ?Authenticatable $user = null): bool
    {
        return self::checkPolicyAbility(ability: 'replicate', address: $address, user: $user, fallbackKey: 'replicate');
    }

    public static function canReorder(?Authenticatable $user = null): bool
    {
        return self::checkPolicyAnyAbility(ability: 'reorder', user: $user, fallbackKey: 'reorder');
    }

    public static function allows(string $action, ?Model $address = null, ?Authenticatable $user = null): bool
    {
        return match ($action) {
            'viewAny' => self::canViewAny($user),
            'view' => $address instanceof Model ? self::canView($address, $user) : self::canViewAny($user),
            'create' => self::canCreate($user),
            'update' => $address instanceof Model ? self::canUpdate($address, $user) : false,
            'delete' => $address instanceof Model ? self::canDelete($address, $user) : false,
            'deleteAny' => self::canDeleteAny($user),
            'restore' => $address instanceof Model ? self::canRestore($address, $user) : false,
            'forceDelete' => $address instanceof Model ? self::canForceDelete($address, $user) : false,
            'forceDeleteAny' => self::canForceDeleteAny($user),
            'restoreAny' => self::canRestoreAny($user),
            'replicate' => $address instanceof Model ? self::canReplicate($address, $user) : false,
            'reorder' => self::canReorder($user),
            default => false,
        };
    }

    public static function toResponse(string $action, ?Model $address = null, ?Authenticatable $user = null): Response
    {
        return self::allows(action: $action, address: $address, user: $user)
            ? Response::allow()
            : Response::deny();
    }

    public static function hasRegisteredPolicy(): bool
    {
        return Gate::getPolicyFor(AddressModels::addressClass()) !== null;
    }

    public static function usesPolicyChecks(): bool
    {
        return match (self::authorizationMode()) {
            'policy' => true,
            'fallback' => false,
            default => self::hasRegisteredPolicy(),
        };
    }

    public static function authorizationMode(): string
    {
        $mode = (string) config('addressing.authorization.mode', 'auto');

        return in_array($mode, ['auto', 'policy', 'fallback'], true) ? $mode : 'auto';
    }

    private static function checkPolicyAbility(
        string $ability,
        Model $address,
        ?Authenticatable $user,
        string $fallbackKey,
    ): bool {
        $user ??= auth()->user();

        if ($user === null) {
            return false;
        }

        if (self::usesPolicyChecks()) {
            return $user->can($ability, $address);
        }

        return (bool) config("addressing.authorization.fallback.{$fallbackKey}", false);
    }

    private static function checkPolicyAnyAbility(
        string $ability,
        ?Authenticatable $user,
        string $fallbackKey,
    ): bool {
        $user ??= auth()->user();

        if ($user === null) {
            return false;
        }

        if (self::usesPolicyChecks()) {
            return $user->can($ability, AddressModels::addressClass());
        }

        return (bool) config("addressing.authorization.fallback.{$fallbackKey}", false);
    }
}
