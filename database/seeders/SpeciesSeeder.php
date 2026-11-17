<?php

namespace Database\Seeders;

use App\Models\Species;
use Illuminate\Database\Seeder;

class SpeciesSeeder extends Seeder
{
    public function run(): void
    {
        $species = [
            ['common_name' => 'Orangutan Sumatra', 'scientific_name' => 'Pongo abelii', 'conservation_status' => 'Critically Endangered'],
            ['common_name' => 'Harimau Sumatra', 'scientific_name' => 'Panthera tigris sumatrae', 'conservation_status' => 'Critically Endangered'],
            ['common_name' => 'Badak Sumatra', 'scientific_name' => 'Dicerorhinus sumatrensis', 'conservation_status' => 'Critically Endangered'],
            ['common_name' => 'Gajah Sumatra', 'scientific_name' => 'Elephas maximus sumatranus', 'conservation_status' => 'Critically Endangered'],
            ['common_name' => 'Rusa Sambar', 'scientific_name' => 'Rusa unicolor', 'conservation_status' => 'Vulnerable'],
        ];

        foreach ($species as $sp) {
            Species::updateOrCreate(
                ['scientific_name' => $sp['scientific_name']],
                $sp
            );
        }
    }
}