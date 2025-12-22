<?php

namespace App\Policies;

use App\Models\Game;
use App\Models\User;

class GamePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Game $game): bool
    {
        return $game->visibility === 'public' || $game->playerIndexFor($user) !== null;
    }

    /**
     * Determine whether the user can start the game.
     */
    public function start(User $user, Game $game): bool
    {
        return $game->user_id === $user->id;
    }

    /**
     * Determine whether the user can play an action.
     */
    public function play(User $user, Game $game): bool
    {
        return $game->status === 'active' && $game->playerIndexFor($user) !== null;
    }

    /**
     * Determine whether the user can join the game.
     */
    public function join(User $user, Game $game): bool
    {
        if ($game->status !== 'waiting') {
            return false;
        }

        if ($game->playerIndexFor($user) !== null) {
            return false;
        }

        if ($game->playersCount() >= 2) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can leave the game.
     */
    public function leave(User $user, Game $game): bool
    {
        return $game->playerIndexFor($user) !== null;
    }
}
