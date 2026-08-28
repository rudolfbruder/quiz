<?php

declare(strict_types=1);

namespace App\Domains\Source\Policies;

use App\Domains\Source\Models\Source;
use App\Models\User;

/**
 * A source belongs to the person who uploaded it and to nobody else.
 *
 * The policy is wired to the model with `#[UsePolicy]`, since auto-discovery cannot
 * resolve a policy for a model living outside `App\Models`.
 */
class SourcePolicy
{
    /**
     * Determine whether the user may see this source.
     */
    public function view(User $user, Source $source): bool
    {
        return $user->id === $source->user_id;
    }

    /**
     * Determine whether the user may delete this source.
     */
    public function delete(User $user, Source $source): bool
    {
        return $user->id === $source->user_id;
    }

    /**
     * Determine whether the user may queue this source for analysis again.
     *
     * Whether the source is in a state that can be retried is the action's
     * business; this only answers whose document it is.
     */
    public function retry(User $user, Source $source): bool
    {
        return $user->id === $source->user_id;
    }
}
