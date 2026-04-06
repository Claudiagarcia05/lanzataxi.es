<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasRole = Schema::hasColumn('users', 'role');
        $hasPhone = Schema::hasColumn('users', 'phone');
        $hasWalletBalance = Schema::hasColumn('users', 'wallet_balance');
        $hasIsDisabled = Schema::hasColumn('users', 'is_disabled');
        $hasDisabledAt = Schema::hasColumn('users', 'disabled_at');
        $hasAvatar = Schema::hasColumn('users', 'avatar');

        if ($hasRole && $hasPhone && $hasWalletBalance && $hasIsDisabled && $hasDisabledAt && $hasAvatar) {
            return;
        }

        Schema::table('users', function (Blueprint $table) use (
            $hasRole,
            $hasPhone,
            $hasWalletBalance,
            $hasIsDisabled,
            $hasDisabledAt,
            $hasAvatar
        ) {
            if (!$hasRole) {
                $table->enum('role', ['pasajero', 'conductor', 'admin'])->default('pasajero');
            }

            if (!$hasPhone) {
                $table->string('phone')->nullable();
            }

            if (!$hasWalletBalance) {
                $table->decimal('wallet_balance', 10, 2)->default(0);
            }

            if (!$hasIsDisabled) {
                $table->boolean('is_disabled')->default(false);
            }

            if (!$hasDisabledAt) {
                $table->timestamp('disabled_at')->nullable();
            }

            if (!$hasAvatar) {
                $table->string('avatar')->nullable();
            }
        });
    }

    public function down(): void
    {
        $columnsToDrop = [];
        foreach (['avatar', 'disabled_at', 'is_disabled', 'wallet_balance', 'phone', 'role'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                $columnsToDrop[] = $column;
            }
        }

        if (empty($columnsToDrop)) {
            return;
        }

        Schema::table('users', function (Blueprint $table) use ($columnsToDrop) {
            $table->dropColumn($columnsToDrop);
        });
    }
};
