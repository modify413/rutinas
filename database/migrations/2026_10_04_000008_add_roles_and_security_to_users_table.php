<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('admin')->after('username');
            $table->foreignId('created_by')->nullable()->after('role')
                ->constrained('users')->nullOnDelete();
            $table->string('security_question', 500)->nullable()->after('created_by');
            $table->string('security_answer')->nullable()->after('security_question');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['role', 'security_question', 'security_answer']);
        });
    }
};
