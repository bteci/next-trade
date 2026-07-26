<?php

namespace Database\Seeders;

use App\Services\SimulationConfigService;
use Illuminate\Database\Seeder;

class SimulationSettingsSeeder extends Seeder
{
    public function run(SimulationConfigService $service): void
    {
        $service->createDefaultSettings(overwriteExisting: false);
        $this->command->info('  Seeded 4 simulation difficulty settings (Normal active by default).');
    }
}
