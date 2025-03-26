<?php

use Illuminate\Database\Seeder;
use App\Models\Remise;

class RemisesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Remise::create([
            'id' => 1,
            'pourcentage' => 0,
        ]);

        // Message de confirmation
        $this->command->info('Remise created');
    }
}
