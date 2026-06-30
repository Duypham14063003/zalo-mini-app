<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_launch_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->string('channel');
            $table->string('entry_type')->default('public_id');
            $table->string('public_identifier');
            $table->string('miniapp_path')->nullable();
            $table->text('launch_url')->nullable();
            $table->text('qr_payload')->nullable();
            $table->string('qr_asset_path')->nullable();
            $table->string('status')->default('draft');
            $table->json('metadata')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamps();

            $table->unique(['game_id', 'channel']);
            $table->index(['workspace_id', 'channel']);
            $table->index(['public_identifier']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_launch_links');
    }
};
