<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')      // creator/owner
                  ->constrained()
                  ->cascadeOnDelete();
            $table->string('status')->default('active');
            $table->unsignedInteger('player_count')->default(2);
            $table->unsignedBigInteger('seed')->nullable();
            $table->json('state');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
