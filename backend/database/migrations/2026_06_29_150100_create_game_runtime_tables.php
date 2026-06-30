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
        Schema::create('game_themes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->string('primary_color')->default('#f9c667');
            $table->string('secondary_color')->default('#fff8e4');
            $table->string('accent_color')->default('#d79e2f');
            $table->string('background_style')->nullable();
            $table->string('background_asset_path')->nullable();
            $table->string('logo_asset_path')->nullable();
            $table->json('theme_tokens')->nullable();
            $table->timestamps();

            $table->unique('game_id');
        });

        Schema::create('game_content_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->string('block_key');
            $table->string('label')->nullable();
            $table->text('content_text')->nullable();
            $table->json('content_payload')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['game_id', 'block_key']);
        });

        Schema::create('game_form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->string('field_key');
            $table->string('type');
            $table->string('label');
            $table->string('placeholder')->nullable();
            $table->string('help_text')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('options')->nullable();
            $table->json('validation_rules')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['game_id', 'field_key']);
        });

        Schema::create('game_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->boolean('requires_reward_code')->default(false);
            $table->unsignedInteger('max_spins_per_player')->default(1);
            $table->unsignedInteger('max_uses_per_reward_code')->nullable();
            $table->string('claim_strategy')->default('instant');
            $table->string('redirect_strategy')->default('close_app');
            $table->json('rules_payload')->nullable();
            $table->timestamps();

            $table->unique('game_id');
        });

        Schema::create('game_redirects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->string('action')->default('close_app');
            $table->string('target_type')->nullable();
            $table->string('target_value')->nullable();
            $table->string('fallback_value')->nullable();
            $table->boolean('is_primary')->default(true);
            $table->timestamps();
        });

        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->string('public_id')->unique();
            $table->string('full_name')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('zalo_user_id')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('prizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('image_asset_path')->nullable();
            $table->string('inventory_type')->default('quota');
            $table->unsignedInteger('quota')->nullable();
            $table->unsignedInteger('awarded_count')->default(0);
            $table->unsignedInteger('weight')->default(0);
            $table->string('value_label')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['game_id', 'code']);
        });

        Schema::create('reward_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prize_id')->nullable()->constrained('prizes')->nullOnDelete();
            $table->foreignId('last_used_by_player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->string('code')->unique();
            $table->string('status')->default('active');
            $table->unsignedInteger('max_uses')->default(1);
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('player_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->json('payload');
            $table->string('source')->default('mini_app');
            $table->timestamp('submitted_at');
            $table->timestamps();
        });

        Schema::create('spin_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_submission_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reward_code_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('pending');
            $table->string('failure_reason')->nullable();
            $table->string('idempotency_key')->nullable();
            $table->timestamp('attempted_at');
            $table->timestamps();

            $table->unique(['game_id', 'idempotency_key']);
        });

        Schema::create('spin_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('spin_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prize_id')->nullable()->constrained('prizes')->nullOnDelete();
            $table->string('result_type')->default('prize');
            $table->string('claim_status')->default('pending');
            $table->json('awarded_payload')->nullable();
            $table->timestamp('resolved_at');
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();

            $table->unique('spin_attempt_id');
        });

        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('spin_result_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('claimed');
            $table->string('claim_action')->nullable();
            $table->string('redirect_target')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique('spin_result_id');
        });

        Schema::create('integration_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider');
            $table->string('connection_key');
            $table->string('status')->default('active');
            $table->json('config')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('game_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_type')->default('user');
            $table->string('action');
            $table->string('target_type');
            $table->string('target_id');
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('integration_connections');
        Schema::dropIfExists('claims');
        Schema::dropIfExists('spin_results');
        Schema::dropIfExists('spin_attempts');
        Schema::dropIfExists('player_submissions');
        Schema::dropIfExists('reward_codes');
        Schema::dropIfExists('prizes');
        Schema::dropIfExists('players');
        Schema::dropIfExists('game_redirects');
        Schema::dropIfExists('game_rules');
        Schema::dropIfExists('game_form_fields');
        Schema::dropIfExists('game_content_blocks');
        Schema::dropIfExists('game_themes');
    }
};
