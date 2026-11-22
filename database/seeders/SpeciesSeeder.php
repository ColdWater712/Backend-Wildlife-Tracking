<?php

namespace Database\Seeders;

use App\Models\Species;
use Illuminate\Database\Seeder;

class SpeciesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['Orangutan Sumatra', 'Pongo abelii', 'Critically Endangered'],
            ['Harimau Sumatra', 'Panthera tigris sumatrae', 'Critically Endangered'],
            ['Badak Sumatra', 'Dicerorhinus sumatrensis', 'Critically Endangered'],
            ['Gajah Sumatra', 'Elephas maximus sumatranus', 'Critically Endangered'],
            ['Rusa Sambar', 'Rusa unicolor', 'Vulnerable'],
        ] as [$common, $scientific, $status]) {
            Species::updateOrCreate(['scientific_name' => $scientific], ['common_name' => $common, 'conservation_status' => $status]);
        }
    }
}
