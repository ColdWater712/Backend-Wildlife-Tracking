<?php

namespace Database\Seeders;

use App\Models\Animal;
use App\Models\Species;
use Illuminate\Database\Seeder;

class AnimalSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['code' => 'ORN-01', 'species' => 'Pongo abelii', 'birth_date' => '2023-08-04', 'gender' => 'male'],
            ['code' => 'ORN-02', 'species' => 'Pongo abelii', 'birth_date' => '2020-05-12', 'gender' => 'female'],
            ['code' => 'HRM-01', 'species' => 'Panthera tigris sumatrae', 'birth_date' => '2019-12-28', 'gender' => 'unknown'],
            ['code' => 'BDK-01', 'species' => 'Dicerorhinus sumatrensis', 'birth_date' => '2019-11-21', 'gender' => 'female'],
            ['code' => 'GJS-01', 'species' => 'Elephas maximus sumatranus', 'birth_date' => '2022-03-18', 'gender' => 'female'],
            ['code' => 'RSA-01', 'species' => 'Rusa unicolor', 'birth_date' => '2014-02-14', 'gender' => 'female'],
        ];

        foreach ($data as $row) {
            $species = Species::where('scientific_name', $row['species'])->first();
            if (!$species) continue;

            Animal::updateOrCreate(
                ['name' => $row['code']],
                [
                    'species_id' => $species->species_id,
                    'name' => $row['code'],
                    'birth_date' => $row['birth_date'],
                    'gender' => $row['gender'],
                    'is_active' => true,
                ]
            );
        }
    }
}