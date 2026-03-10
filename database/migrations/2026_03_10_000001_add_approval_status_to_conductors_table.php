<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conductors', function (Blueprint $table) {
            // Estado de aprobación del taxista (separado de is_active que es “en línea / disponibilidad”).
            // Usamos string para evitar problemas de enum en distintos motores.
            $table->string('approval_status', 20)->default('pending')->after('is_active');
            $table->timestamp('approved_at')->nullable()->after('approval_status');
            $table->timestamp('rejected_at')->nullable()->after('approved_at');
        });

        // Los conductores existentes se consideran aprobados (para no bloquear a los ya operativos).
        DB::table('conductors')
            ->whereNull('approval_status')
            ->update([
                'approval_status' => 'approved',
                'approved_at' => DB::raw('COALESCE(approved_at, created_at)'),
            ]);

        // Si la columna se creó con default 'pending', el whereNull no aplica;
        // aun así, marcamos como approved a todos los registros existentes.
        DB::table('conductors')->update([
            'approval_status' => 'approved',
            'approved_at' => DB::raw('COALESCE(approved_at, created_at)'),
            'rejected_at' => null,
        ]);
    }

    public function down(): void
    {
        Schema::table('conductors', function (Blueprint $table) {
            $table->dropColumn(['approval_status', 'approved_at', 'rejected_at']);
        });
    }
};
