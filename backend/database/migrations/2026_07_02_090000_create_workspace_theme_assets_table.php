<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspace_theme_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slot_type', 50);
            $table->string('display_name');
            $table->string('asset_path');
            $table->string('mime_type', 100)->nullable();
            $table->string('source_kind', 20)->default('upload');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['slot_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_theme_assets');
    }
};
