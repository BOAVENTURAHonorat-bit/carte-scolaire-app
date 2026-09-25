<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use Symfony\Component\HttpFoundation\Response;

class PageController extends Controller
{
    /**
     * Page de garde publique : présentation de l'application, sans connexion.
     */
    public function home(): \Illuminate\View\View
    {
        $school = School::with('classes')->first();
        $classCount = $school?->classes->count() ?? 0;
        $studentCount = $school ? Student::where('school_id', $school->id)->count() : 0;

        return view('welcome', compact('school', 'classCount', 'studentCount'));
    }

    /**
     * Aperçu public d'une carte scolaire de démonstration, sans authentification
     * ni donnée réelle en base.
     */
    public function apercu(): Response
    {
        $school = new School([
            'country' => "Côte d'Ivoire",
            'school_type' => 'École Maternelle',
            'name' => 'Groupe Scolaire Les Petits Génies',
            'primary_color' => '#1E3A8A',
            'secondary_color' => '#F59E0B',
            'exit_hours' => '11h30 - 16h30',
            'late_penalty_note' => 'Tout retard entraînera une pénalité de 500 FCFA.',
            'address' => 'Cocody, Abidjan',
            'phone' => '+225 01 02 03 04 05',
            'email' => 'contact@petitsgenies.ci',
        ]);
        $school->flag_uri = null;
        $school->representative_photo_uri = null;

        $schoolClass = new SchoolClass([
            'level' => 'Grande Section',
            'section' => 'A',
        ]);

        $student = new Student([
            'last_name' => 'KOUASSI',
            'first_name' => 'Aïcha',
            'birth_date' => '2020-05-10',
        ]);
        $student->setRelation('school', $school);
        $student->setRelation('schoolClass', $schoolClass);
        $student->setRelation('guardians', collect());
        $student->photo_uri = null;

        $html = view('cards.maternelle', [
            'students' => collect([$student]),
            'school' => $school,
        ])->render();

        return response($html);
    }
}
