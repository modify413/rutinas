<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routine_items', function (Blueprint $table) {
            $table->string('gif_path', 1024)->nullable()->after('gif_url');
        });
    }

    public function down(): void
    {
        Schema::table('routine_items', function (Blueprint $table) {
            $table->dropColumn('gif_path');
        });
    }
};
