<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use StellarSkirmish\GameState;

class Game extends Model
{
    use HasFactory, HasUlids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'status',
        'player_count',
        'seed',
        'visibility',
        'config',
        'state',
    ];

    protected function casts(): array
    {
        return [
            'state' => 'array',
            'config' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'game_user')
            ->withPivot('player_index');
    }

    public function addPlayer(User $user, int $index): void
    {
        $this->players()->attach($user->id, ['player_index' => $index]);
    }

    public function playerIndexFor(User $user): ?int
    {
        $pivot = $this->players()->where('users.id', $user->id)->first()?->pivot;

        return $pivot?->player_index;
    }

    public function toGameState(): GameState
    {
        return GameState::fromArray($this->state);
    }

    public function updateFromGameState(GameState $state): void
    {
        $array = $state->toArray();

        $this->state = $array;
        $this->status = $state->gameOver ? 'completed' : 'active';

        $this->save();
    }
}
