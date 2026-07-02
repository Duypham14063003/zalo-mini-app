<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE workspace_theme_assets ALTER COLUMN workspace_id DROP NOT NULL');
        DB::statement('UPDATE workspace_theme_assets SET workspace_id = NULL');
        DB::statement('DROP INDEX IF EXISTS workspace_theme_assets_workspace_id_slot_type_is_active_index');
        DB::statement('CREATE INDEX IF NOT EXISTS workspace_theme_assets_slot_type_is_active_index ON workspace_theme_assets (slot_type, is_active)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS workspace_theme_assets_slot_type_is_active_index');
        DB::statement('CREATE INDEX IF NOT EXISTS workspace_theme_assets_workspace_id_slot_type_is_active_index ON workspace_theme_assets (workspace_id, slot_type, is_active)');
    }
};
