<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Support\ImageDataUri;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class StudentCardController extends Controller
{
    /**
     * Points equivalent of an 85.6mm x 54mm (ID-1 / bank card) landscape page.
     */
    private const PAPER_SIZE = [0, 0, 242.65, 153.07];

    /**
     * Liste des classes avec un bouton d'export PDF pour toute la classe.
     */
    public function classesIndex(): View
    {
        $classes = SchoolClass::with('school')
            ->withCount('students')
            ->orderBy('school_id')
            ->orderBy('level')
            ->orderBy('section')
            ->get();

        $classesBySchool = $classes->groupBy('school_id')->map(function (Collection $group) {
            return [
                'school' => $group->first()->school,
                'classes' => $group,
                'total' => $group->sum('students_count'),
            ];
        })->values();

        return view('classes.index', compact('classesBySchool'));
    }

    public function show(Request $request, Student $student): Response
    {
        $student->load(['school', 'schoolClass', 'guardians']);
        $school = $student->school;
        $side = $this->resolveSide($request);

        $this->attachImageUris(collect([$student]), $school);

        $pdf = Pdf::loadView('cards.maternelle', [
            'students' => collect([$student]),
            'school' => $school,
            'side' => $side,
        ])->setPaper(self::PAPER_SIZE);

        $suffix = $side ? '-'.$side : '';
        $filename = 'carte-'.Str::slug($student->last_name).'-'.Str::slug($student->first_name).$suffix.'.pdf';

        return $pdf->download($filename);
    }

    public function preview(Student $student): Response
    {
        $student->load(['school', 'schoolClass', 'guardians']);
        $school = $student->school;

        $this->attachImageUris(collect([$student]), $school, cropped: false);

        $html = view('cards.maternelle', [
            'students' => collect([$student]),
            'school' => $school,
            'preview' => true,
        ])->render();

        return response($html);
    }

    public function classCards(Request $request, SchoolClass $schoolClass): Response
    {
        set_time_limit(0); // grandes classes : recadrage GD + rendu PDF peuvent dépasser le timeout par défaut

        $schoolClass->load(['school', 'students.guardians']);
        $school = $schoolClass->school;
        $students = $schoolClass->students;
        $side = $this->resolveSide($request);

        $this->attachImageUris($students, $school);

        $pdf = Pdf::loadView('cards.maternelle', [
            'students' => $students,
            'school' => $school,
            'side' => $side,
            'groupBySide' => ! $side,
        ])->setPaper(self::PAPER_SIZE);

        $suffix = $side ? '-'.$side : '';
        $filename = 'cartes-'.Str::slug($schoolClass->level.'-'.$schoolClass->section).$suffix.'.pdf';

        return $pdf->download($filename);
    }

    public function classPreview(SchoolClass $schoolClass): Response
    {
        $schoolClass->load(['school', 'students.guardians']);
        $school = $schoolClass->school;
        $students = $schoolClass->students;

        $this->attachImageUris($students, $school, cropped: false);

        $html = view('cards.maternelle', [
            'students' => $students,
            'school' => $school,
            'preview' => true,
        ])->render();

        return response($html);
    }

    private function resolveSide(Request $request): ?string
    {
        $side = $request->query('side');

        return in_array($side, ['recto', 'verso'], true) ? $side : null;
    }

    /** Rapport largeur/hauteur des cadres photo, pour un recadrage net (sans étirement) côté serveur. */
    private const STUDENT_PHOTO_RATIO = 21.34 / 26.04;

    private const REPRESENTATIVE_PHOTO_RATIO = 1.0;

    private const GUARDIAN_PHOTO_RATIO = 25.33 / 26.4;

    /**
     * @param  bool  $cropped  true (PDF génération) : recadre nettement côté serveur (GD) pour
     *                         éviter le flou de dompdf avec background-image. false (aperçu
     *                         navigateur) : image non recadrée, le CSS object-fit/position du
     *                         navigateur gère l'aperçu en direct pendant le glisser-déposer.
     */
    private function attachImageUris(Collection $students, $school, bool $cropped = true): void
    {
        $school->logo_uri = ImageDataUri::fromStoragePath($school->logo_path);
        $school->stamp_uri = ImageDataUri::fromStoragePath('assets/cachet-signature-nom.png');
        $school->signature_uri = ImageDataUri::fromStoragePath('assets/signature.png');
        $school->watermark_uri = ImageDataUri::fromStoragePath('assets/coeur.png');

        $school->representative_photo_uri = $cropped
            ? ImageDataUri::croppedDataUri($school->representative_photo_path, self::REPRESENTATIVE_PHOTO_RATIO)
            : ImageDataUri::fromStoragePath($school->representative_photo_path);

        foreach ($students as $student) {
            $student->photo_uri = $cropped
                ? ImageDataUri::croppedDataUri(
                    $student->photo_path, self::STUDENT_PHOTO_RATIO,
                    $student->photo_position_x ?? 50, $student->photo_position_y ?? 50
                )
                : ImageDataUri::fromStoragePath($student->photo_path);

            foreach ($student->guardians as $guardian) {
                $guardian->photo_uri = $cropped
                    ? ImageDataUri::croppedDataUri(
                        $guardian->photo_path, self::GUARDIAN_PHOTO_RATIO,
                        $guardian->photo_position_x ?? 50, $guardian->photo_position_y ?? 50,
                        maxDimension: 900
                    )
                    : ImageDataUri::fromStoragePath($guardian->photo_path);
            }
        }
    }
}
