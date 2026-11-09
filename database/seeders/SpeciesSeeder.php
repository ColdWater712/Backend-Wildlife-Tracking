<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SpeciesSeeder extends Seeder
{
    public function run(): void
    {
        $species = [
            ['common_name' => 'Orangutan Sumatra', 'scientific_name' => 'Pongo abelii', 'conservation_status' => 'Critically Endangered'],
            ['common_name' => 'Harimau Sumatra', 'scientific_name' => 'Panthera tigris sumatrae', 'conservation_status' => 'Critically Endangered'],
            ['common_name' => 'Badak Sumatra', 'scientific_name' => 'Dicerorhinus sumatrensis', 'conservation_status' => 'Critically Endangered'],
            ['common_name' => 'Gajah Sumatra', 'scientific_name' => 'Elephas maximus sumatranus', 'conservation_status' => 'Critically Endangered'],
            ['common_name' => 'Beruang Madu', 'scientific_name' => 'Helarctos malayanus', 'conservation_status' => 'Vulnerable'],
        ];

        DB::table('species')->insert($species);
    }
}