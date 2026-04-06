<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'disabled_at')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('disabled_at')->nullable();
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('users', 'disabled_at')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('disabled_at');
        });
    }
};
