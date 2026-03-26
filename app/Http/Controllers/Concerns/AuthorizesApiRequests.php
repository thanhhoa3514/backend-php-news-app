<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

trait AuthorizesApiRequests
{
    protected function currentUser(): User
    {
        /** @var User|null $user */
        $user = auth()->user();

        if (!$user) {
            throw new AuthorizationException('Authentication required.');
        }

        return $user;
    }

    protected function ensureAdmin(?User $user = null): User
    {
        $user ??= $this->currentUser();

        if (!$user->isAdmin()) {
            throw new AuthorizationException('Admin access is required.');
        }

        return $user;
    }

    protected function ensureEditorOrAdmin(?User $user = null): User
    {
        $user ??= $this->currentUser();

        if (!$user->isAdmin() && !$user->hasRole('editor')) {
            throw new AuthorizationException('Editor or admin access is required.');
        }

        return $user;
    }

    protected function ensureOwnerOrAdmin(int $ownerId, ?User $user = null, string $message = 'You do not have access to this resource.'): User
    {
        $user ??= $this->currentUser();

        if (!$user->isAdmin() && (int) $user->id !== (int) $ownerId) {
            throw new AuthorizationException($message);
        }

        return $user;
    }
}
