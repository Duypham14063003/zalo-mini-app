<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_redirects', function (Blueprint $table): void {
            $table->text('message_template')->nullable()->after('fallback_value');
        });
    }

    public function down(): void
    {
        Schema::table('game_redirects', function (Blueprint $table): void {
            $table->dropColumn('message_template');
        });
    }
};
