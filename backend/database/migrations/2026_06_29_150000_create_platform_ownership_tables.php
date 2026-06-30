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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('active');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('active');
            $table->string('timezone')->default('Asia/Ho_Chi_Minh');
            $table->timestamps();
        });

        Schema::create('workspace_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('workspace_owner');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['workspace_id', 'user_id']);
        });

        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('template_type')->default('lucky_wheel');
            $table->string('status')->default('draft');
            $table->text('description')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'slug']);
        });

        Schema::create('game_public_ids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->string('public_id')->unique();
            $table->string('slug')->unique();
            $table->boolean('is_primary')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_public_ids');
        Schema::dropIfExists('games');
        Schema::dropIfExists('workspace_memberships');
        Schema::dropIfExists('workspaces');
        Schema::dropIfExists('accounts');
    }
};
