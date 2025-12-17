<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'state',
    ];

    protected $casts = [
        'state' => 'array',
    ];

    public function creator() : BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function toGameState() : GameState
    {
        return GameState::fromArray($this->state);
    }

    public function updateFromGameState(GameState $state) : void
    {
        $array = $state->toArray();

        $this->state = $array;
        $this->status = $state->gameOver ? 'completed' : 'active';

        $this->save();
    }
}
