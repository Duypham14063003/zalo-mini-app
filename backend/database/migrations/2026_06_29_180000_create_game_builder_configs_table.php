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
        Schema::create('game_builder_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->string('active_step')->default('general');
            $table->string('publication_status')->default('draft');
            $table->json('draft_config')->nullable();
            $table->json('published_config')->nullable();
            $table->timestamp('last_saved_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique('game_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_builder_configs');
    }
};
