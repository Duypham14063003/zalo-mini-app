<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spin_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('spin_attempt_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('spin_result_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('prize_id')->nullable()->constrained('prizes')->nullOnDelete();
            $table->string('user_identifier');
            $table->string('reward_name')->nullable();
            $table->string('reward_type')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('spun_at');
            $table->timestamps();

            $table->index(['game_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spin_logs');
    }
};
