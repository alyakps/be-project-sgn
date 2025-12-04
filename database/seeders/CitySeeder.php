<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\City;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/indonesia_cities.csv');

        if (!file_exists($path)) {
            $this->command->error("CSV not found: $path");
            return;
        }

        if (($handle = fopen($path, 'r')) === false) {
            $this->command->error("Cannot open CSV");
            return;
        }

        // skip header
        fgetcsv($handle);

        $count = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $name = $row[0] ?? null;
            if (!$name) continue;

            City::firstOrCreate(['name' => $name]);

            $count++;
        }

        fclose($handle);

        $this->command->info("Imported $count cities.");
    }
}