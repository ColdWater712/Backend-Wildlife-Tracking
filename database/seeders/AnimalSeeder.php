<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AnimalSeeder extends Seeder {
    public function run(): void {
        DB::table('animals')->insert([
            ['animal_id' => 1, 'species_id' => 1, 'name' => 'SATWA-742', 'birth_date' => '2023-08-04', 'gender' => 'male', 'is_active' => 1],
            ['animal_id' => 2, 'species_id' => 2, 'name' => 'SATWA-281', 'birth_date' => '2019-12-28', 'gender' => 'unknown', 'is_active' => 1],
            ['animal_id' => 3, 'species_id' => 3, 'name' => 'SATWA-803', 'birth_date' => '2019-11-21', 'gender' => 'female', 'is_active' => 1],
            ['animal_id' => 4, 'species_id' => 4, 'name' => 'SATWA-387', 'birth_date' => '2022-03-18', 'gender' => 'female', 'is_active' => 1],
            ['animal_id' => 5, 'species_id' => 5, 'name' => 'SATWA-968', 'birth_date' => '2014-02-14', 'gender' => 'female', 'is_active' => 1],
        ]);
    }
}