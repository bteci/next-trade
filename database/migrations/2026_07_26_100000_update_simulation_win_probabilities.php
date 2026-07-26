<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['easy' => 70.00, 'normal' => 40.00, 'hard' => 30.00, 'extreme' => 5.00] as $difficulty => $rate) {
            DB::table('simulation_settings')
                ->where('difficulty', $difficulty)
                ->update(['win_probability' => $rate]);
        }
    }

    public function down(): void
    {
        foreach (['easy' => 65.00, 'normal' => 50.00, 'hard' => 40.00, 'extreme' => 30.00] as $difficulty => $rate) {
            DB::table('simulation_settings')
                ->where('difficulty', $difficulty)
                ->update(['win_probability' => $rate]);
        }
    }
};
