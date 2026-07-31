<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('member')->after('password');
            $table->boolean('is_active')->default(true)->after('role');
            $table->string('theme')->default('system')->after('is_active');
            $table->string('locale', 5)->default('fr')->after('theme');
            $table->string('avatar_path')->nullable()->after('locale');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'is_active', 'theme', 'locale', 'avatar_path']);
        });
    }
};
