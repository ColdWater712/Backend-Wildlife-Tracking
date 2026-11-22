<?php

namespace Database\Seeders;

use App\Models\Animal;
use App\Models\Species;
use Illuminate\Database\Seeder;

class AnimalSeeder extends Seeder
{
    public function run(): void
    {
        $species = Species::pluck('species_id', 'scientific_name');

        foreach ([
            ['ORN-01', 'Pongo abelii', '2023-08-04', 'male', 'Jantan muda, bantalan pipi belum berkembang, bekas luka kecil di bahu kiri'],
            ['ORN-02', 'Pongo abelii', '2020-05-12', 'female', 'Betina dewasa, sering terlihat bersama anak, rambut kemerahan terang'],
            ['HRM-01', 'Panthera tigris sumatrae', '2019-12-28', 'unknown', 'Belang rapat, telinga kanan sobek di ujung'],
            ['BDK-01', 'Dicerorhinus sumatrensis', '2019-11-21', 'female', 'Cula depan pendek, bekas luka di sisi kanan tubuh'],
            ['GJS-01', 'Elephas maximus sumatranus', '2022-03-18', 'female', 'Gajah muda, telinga kiri berlubang kecil'],
            ['RSA-01', 'Rusa unicolor', '2014-02-14', 'female', null],
        ] as [$code, $scientific, $birth, $gender, $marks]) {
            Animal::updateOrCreate(['name_animal' => $code], [
                'species_id' => $species[$scientific],
                'birth_date' => $birth,
                'gender' => $gender,
                'distinctive_marks' => $marks,
                'is_active' => true,
            ]);
        }
    }
}
