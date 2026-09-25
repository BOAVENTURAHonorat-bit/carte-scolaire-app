<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $school = School::firstOrCreate(
            ['name' => 'Groupe Scolaire Les Petits Génies'],
            [
                'country' => "Côte d'Ivoire",
                'school_type' => 'École Maternelle',
                'primary_color' => '#1E3A8A',
                'secondary_color' => '#F59E0B',
                'exit_hours' => '11h30 - 16h30',
                'late_penalty_note' => 'Tout retard entraînera une pénalité de 500 FCFA.',
                'address' => 'Cocody, Abidjan',
                'phone' => '+225 01 02 03 04 05',
                'email' => 'contact@petitsgenies.ci',
            ]
        );

        // Structure actuelle de l'école : Maternelle 1 (A/B) et Maternelle 2 (A/B).
        // D'autres classes pourront être ajoutées plus tard sans limite technique.
        foreach (['Maternelle 1', 'Maternelle 2'] as $level) {
            foreach (['A', 'B'] as $section) {
                SchoolClass::firstOrCreate([
                    'school_id' => $school->id,
                    'level' => $level,
                    'section' => $section,
                ]);
            }
        }

        User::factory()->create([
            'name' => 'Secrétaire',
            'email' => 'secretaire@ecole.test',
            'role' => 'secretaire',
        ]);

        User::factory()->create([
            'name' => 'Directeur Général',
            'email' => 'dg@ecole.test',
            'role' => 'dg',
        ]);
    }
}
