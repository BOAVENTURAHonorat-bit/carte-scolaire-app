<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentGuardian;
use App\Support\ImageDataUri;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Smalot\PdfParser\Parser as PdfParser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentController extends Controller
{
    /**
     * En-tête attendu du CSV d'import en masse. Les colonnes accompagnateur*
     * sont optionnelles (laisser vide si l'élève n'a pas 3 accompagnateurs).
     */
    private const IMPORT_HEADER = [
        'nom', 'prenom', 'date_naissance', 'classe', 'telephone',
        'accompagnateur1_nom', 'accompagnateur1_prenom',
        'accompagnateur2_nom', 'accompagnateur2_prenom',
        'accompagnateur3_nom', 'accompagnateur3_prenom',
    ];

    /**
     * Liste de tous les élèves : recherche par nom, filtre par classe et par statut.
     * Les actions de gestion (modifier/déplacer/supprimer) restent réservées au DG dans la vue.
     */
    public function index(Request $request): View
    {
        $query = Student::with(['school', 'schoolClass']);

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('last_name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$search}%"])
                    ->orWhereRaw("CONCAT(last_name, ' ', first_name) like ?", ["%{$search}%"]);
            });
        }

        if ($classId = $request->query('class_id')) {
            $query->where('school_class_id', $classId);
        }

        if ($status = $request->query('status')) {
            if (in_array($status, ['active', 'inactive'], true)) {
                $query->where('status', $status);
            }
        }

        // Hors recherche par nom, on garde les élèves groupés par classe plutôt que
        // de les mélanger alphabétiquement entre plusieurs classes.
        if (! $search) {
            $query->orderBy('school_class_id');
        }

        $students = $query->orderBy('last_name')->orderBy('first_name')
            ->paginate(50)
            ->withQueryString();

        $classes = SchoolClass::with('school')->orderBy('level')->orderBy('section')->get();

        return view('students.index', compact('students', 'classes'));
    }

    /**
     * DG : basculer le statut actif / inactif d'un élève.
     */
    public function toggleStatus(Student $student): RedirectResponse
    {
        $student->update([
            'status' => $student->status === 'active' ? 'inactive' : 'active',
        ]);

        return redirect()
            ->back()
            ->with('status', "Statut de {$student->first_name} {$student->last_name} mis à jour.");
    }

    /**
     * Secrétaire : formulaire d'ajout d'un élève (recto + verso).
     */
    public function create(): View
    {
        $classes = SchoolClass::with('school')->orderBy('level')->get();
        $classesData = $this->classesDataForLivePreview($classes);

        return view('students.create', compact('classes', 'classesData'));
    }

    /**
     * Données des classes (avec l'identité visuelle de leur établissement) au
     * format attendu par l'aperçu de carte en direct côté client (JS).
     */
    private function classesDataForLivePreview(\Illuminate\Support\Collection $classes): array
    {
        return $classes->mapWithKeys(function (SchoolClass $class) {
            $school = $class->school;

            return [
                $class->id => [
                    'level' => $class->level,
                    'section' => $class->section,
                    'school' => [
                        'name' => $school->name,
                        'primary_color' => $school->primary_color,
                        'logo_url' => $school->logo_path ? Storage::url($school->logo_path) : null,
                        'exit_hours' => $school->exit_hours,
                        'late_penalty_note' => $school->late_penalty_note,
                        'email' => $school->email,
                        'address' => $school->address,
                        'phone' => $school->phone,
                    ],
                ],
            ];
        })->all();
    }

    /**
     * Secrétaire : enregistrer un nouvel élève avec ses photos et accompagnateurs.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateStudent($request);

        $student = new Student([
            'school_id' => SchoolClass::findOrFail($data['school_class_id'])->school_id,
            'school_class_id' => $data['school_class_id'],
            'last_name' => $data['last_name'],
            'first_name' => $data['first_name'],
            'birth_date' => $data['birth_date'],
            'parent_phone' => $data['parent_phone'] ?? null,
        ]);

        if ($request->hasFile('photo')) {
            $student->photo_path = $request->file('photo')->store('students/photos', 'public');
        }

        $student->photo_position_x = $data['photo_position_x'] ?? 50;
        $student->photo_position_y = $data['photo_position_y'] ?? 50;

        $student->save();

        $this->saveGuardians($request, $student);

        return redirect()
            ->route('students.create')
            ->with('status', "L'élève {$student->first_name} {$student->last_name} a été ajouté avec succès.");
    }

    /**
     * Secrétaire : aperçu "en direct" de la vraie carte (recto + verso) pendant la saisie,
     * à partir de champs pas encore enregistrés. Rejoué à chaque modification du formulaire
     * (débouncé côté client) — reconstitue un élève/accompagnateurs en mémoire (non persistés)
     * et réutilise exactement le même template que la carte PDF définitive, donc le rendu est
     * fidèle plutôt qu'une approximation. Volontairement permissif (aucune validation stricte) :
     * un aperçu ne doit jamais échouer juste parce qu'un champ est encore incomplet.
     */
    public function previewDraft(Request $request): Response
    {
        $schoolClass = $request->filled('school_class_id')
            ? SchoolClass::with('school')->find($request->input('school_class_id'))
            : null;

        $school = $schoolClass?->school ?? new School([
            'name' => 'École',
            'primary_color' => '#1E3A8A',
            'secondary_color' => '#F59E0B',
        ]);

        $birthDate = null;
        if ($request->filled('birth_date')) {
            try {
                $birthDate = \Carbon\Carbon::parse($request->input('birth_date'));
            } catch (\Throwable) {
                // date incomplète pendant la saisie : on ignore simplement l'âge pour l'aperçu.
            }
        }

        $student = new Student([
            'last_name' => (string) $request->input('last_name', ''),
            'first_name' => (string) $request->input('first_name', ''),
            'parent_phone' => $request->input('parent_phone'),
            'photo_position_x' => (int) $request->input('photo_position_x', 50),
            'photo_position_y' => (int) $request->input('photo_position_y', 50),
        ]);
        $student->birth_date = $birthDate;
        $student->setRelation('schoolClass', $schoolClass);

        if ($request->hasFile('photo')) {
            $student->photo_uri = ImageDataUri::fromUploadedFile($request->file('photo'));
        }

        $guardianFiles = $request->file('guardians', []);
        $guardians = collect();

        foreach ((array) $request->input('guardians', []) as $index => $guardianData) {
            $guardian = new StudentGuardian([
                'first_name' => $guardianData['first_name'] ?? '',
                'last_name' => $guardianData['last_name'] ?? '',
                'photo_position_x' => (int) ($guardianData['photo_position_x'] ?? 50),
                'photo_position_y' => (int) ($guardianData['photo_position_y'] ?? 50),
            ]);

            if ($photo = $guardianFiles[$index]['photo'] ?? null) {
                $guardian->photo_uri = ImageDataUri::fromUploadedFile($photo);
            }

            $guardians->push($guardian);
        }

        $student->setRelation('guardians', $guardians);

        $school->logo_uri = $school->exists ? ImageDataUri::fromStoragePath($school->logo_path) : null;
        $school->stamp_uri = $school->exists ? ImageDataUri::fromStoragePath('assets/cachet-signature-nom.png') : null;
        $school->watermark_uri = $school->exists ? ImageDataUri::fromStoragePath('assets/coeur.png') : null;
        $school->representative_photo_uri = $school->exists ? ImageDataUri::fromStoragePath($school->representative_photo_path) : null;

        $html = view('cards.maternelle', [
            'students' => collect([$student]),
            'school' => $school,
            'preview' => true,
            'draftPreview' => true,
        ])->render();

        return response($html);
    }

    /**
     * DG : formulaire de modification (changer de classe, corriger les infos, la carte).
     */
    public function edit(Student $student): View
    {
        $student->load('guardians');
        $classes = SchoolClass::with('school')->orderBy('level')->get();

        return view('students.edit', compact('student', 'classes'));
    }

    /**
     * DG : enregistrer les modifications, y compris le changement de classe.
     */
    public function update(Request $request, Student $student): RedirectResponse
    {
        $data = $this->validateStudent($request, $student);

        $student->fill([
            'school_id' => SchoolClass::findOrFail($data['school_class_id'])->school_id,
            'school_class_id' => $data['school_class_id'],
            'last_name' => $data['last_name'],
            'first_name' => $data['first_name'],
            'birth_date' => $data['birth_date'],
            'parent_phone' => $data['parent_phone'] ?? null,
        ]);

        if ($request->hasFile('photo')) {
            if ($student->photo_path) {
                Storage::disk('public')->delete($student->photo_path);
            }
            $student->photo_path = $request->file('photo')->store('students/photos', 'public');
        }

        $student->photo_position_x = $data['photo_position_x'] ?? 50;
        $student->photo_position_y = $data['photo_position_y'] ?? 50;

        $student->save();

        $this->saveGuardians($request, $student, isUpdate: true);

        if ($request->input('from') === 'preview') {
            return redirect()
                ->route('students.card.preview', $student)
                ->with('status', "La carte de {$student->first_name} {$student->last_name} a été mise à jour.");
        }

        return redirect()
            ->route('students.index')
            ->with('status', "La carte de {$student->first_name} {$student->last_name} a été mise à jour.");
    }

    /**
     * Enregistre uniquement le cadrage (position) des photos déjà présentes sur la carte,
     * utilisé par le mode "Modifier" directement sur la page d'aperçu.
     */
    public function updatePhotoPosition(Request $request, Student $student): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'photo_position_x' => ['nullable', 'integer', 'min:0', 'max:100'],
            'photo_position_y' => ['nullable', 'integer', 'min:0', 'max:100'],
            'guardians' => ['array'],
            'guardians.*.id' => ['required', 'integer', 'exists:student_guardians,id'],
            'guardians.*.photo_position_x' => ['nullable', 'integer', 'min:0', 'max:100'],
            'guardians.*.photo_position_y' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $student->update([
            'photo_position_x' => $data['photo_position_x'] ?? 50,
            'photo_position_y' => $data['photo_position_y'] ?? 50,
        ]);

        foreach ($data['guardians'] ?? [] as $guardianData) {
            $student->guardians()
                ->where('id', $guardianData['id'])
                ->update([
                    'photo_position_x' => $guardianData['photo_position_x'] ?? 50,
                    'photo_position_y' => $guardianData['photo_position_y'] ?? 50,
                ]);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * DG : supprimer la carte d'un élève.
     */
    public function destroy(Student $student): RedirectResponse
    {
        foreach ($student->guardians as $guardian) {
            if ($guardian->photo_path) {
                Storage::disk('public')->delete($guardian->photo_path);
            }
        }

        if ($student->photo_path) {
            Storage::disk('public')->delete($student->photo_path);
        }

        $name = "{$student->first_name} {$student->last_name}";
        $student->delete();

        return redirect()
            ->route('students.index')
            ->with('status', "La carte de {$name} a été supprimée.");
    }

    /**
     * Formulaire d'envoi groupé des photos pour toute une classe : les fichiers
     * sont attribués dans l'ordre où ils sont sélectionnés aux élèves de la
     * classe (triés comme sur la liste), à vérifier ensuite un par un.
     */
    public function bulkPhotosForm(SchoolClass $schoolClass): View
    {
        $schoolClass->load('school');
        $students = $schoolClass->students()->orderBy('last_name')->orderBy('first_name')->get();

        return view('students.bulk-photos', compact('schoolClass', 'students'));
    }

    public function bulkPhotosStore(Request $request, SchoolClass $schoolClass): RedirectResponse
    {
        $request->validate([
            'photos' => ['required', 'array'],
            'photos.*' => ['image', 'max:15360'],
        ]);

        $students = $schoolClass->students()->orderBy('last_name')->orderBy('first_name')->get();
        $photos = array_values($request->file('photos'));

        $assigned = 0;
        $results = [];

        foreach ($photos as $index => $photo) {
            $student = $students->get($index);

            if (! $student) {
                break; // plus de photos que d'élèves dans la classe
            }

            if ($student->photo_path) {
                Storage::disk('public')->delete($student->photo_path);
            }

            $student->update([
                'photo_path' => $photo->store('students/photos', 'public'),
                'photo_position_x' => 50,
                'photo_position_y' => 50,
            ]);

            $results[] = "{$student->first_name} {$student->last_name}";
            $assigned++;
        }

        $status = "{$assigned} photo(s) attribuée(s) dans l'ordre. Vérifiez chaque élève ci-dessous.";
        if (count($photos) > $students->count()) {
            $status .= ' '.(count($photos) - $students->count())." photo(s) en trop n'ont pas été utilisées.";
        }

        return redirect()
            ->route('students.photos.bulk-form', $schoolClass)
            ->with('status', $status)
            ->with('bulkPhotoResults', $results);
    }

    /**
     * Secrétaire : télécharger un modèle de fichier CSV pour l'import en masse.
     */
    public function importTemplate(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 pour Excel
            fputcsv($out, self::IMPORT_HEADER);
            fputcsv($out, [
                'KOUASSI', 'Aya', '2020-05-10', 'Maternelle 1 A', '0198765432',
                'KOUASSI', 'Marie', 'KOUASSI', 'Jean', '', '',
            ]);
            fclose($out);
        }, 'modele-import-eleves.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Secrétaire : importer une liste d'élèves depuis un fichier CSV ou PDF
     * (nom, prénom, date de naissance, classe), en attribuant en même temps
     * les photos de toute la classe. Chaque photo est reliée à l'élève dont
     * le nom apparaît dans son nom de fichier (ex: "KOUASSI_Aya.jpg"), pas par
     * ordre de sélection : plus sûr, l'ordre des fichiers importe peu.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,pdf', 'max:4096'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['image', 'max:15360'],
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension !== 'pdf') {
            $firstBytes = file_get_contents($file->getRealPath(), false, null, 0, 4) ?: '';
            if (str_starts_with($firstBytes, "\xFF\xFE") || str_starts_with($firstBytes, "\xFE\xFF")) {
                return redirect()
                    ->back()
                    ->withErrors(['file' => "Ce fichier semble enregistré dans un format texte Unicode (UTF-16) non pris en charge. Dans Excel, réenregistrez-le via \"Fichier > Enregistrer sous\" en choisissant le type \"CSV UTF-8 (délimité par des virgules)\", puis réessayez."]);
            }
        }

        $rows = $extension === 'pdf'
            ? $this->extractRowsFromPdf($file->getRealPath())
            : $this->extractRowsFromCsv($file->getRealPath());

        if ($rows === null) {
            return redirect()
                ->back()
                ->withErrors(['file' => 'La première ligne du fichier CSV doit contenir exactement ces colonnes (virgule ou point-virgule accepté) : '.implode(', ', self::IMPORT_HEADER).'. Ou, pour un import simplifié sans photos/téléphone/accompagnateurs : nom, prenom, date_naissance, classe. Téléchargez le modèle ci-dessus pour être sûr du format.']);
        }

        $classes = SchoolClass::all()->keyBy(fn ($c) => strtolower(trim($c->level.' '.$c->section)));
        $schools = School::all();

        $imported = 0;
        $errors = [];
        $createdStudents = [];
        $createdClasses = [];

        foreach ($rows as $row) {
            $line = $row['line'];
            $lastName = trim((string) $row['last_name']);
            $firstName = trim((string) $row['first_name']);
            $birthDate = trim((string) $row['birth_date']);
            $classLabel = trim((string) $row['class_label']);
            $classKey = strtolower($classLabel);

            if ($lastName === '' || $firstName === '') {
                $errors[] = "Ligne {$line} : nom ou prénom manquant ou non reconnu.";
                continue;
            }

            if (! $classes->has($classKey)) {
                if ($classLabel === '') {
                    $errors[] = "Ligne {$line} : classe non reconnue sur cette ligne.";
                    continue;
                }

                if ($schools->count() !== 1) {
                    $errors[] = "Ligne {$line} : classe \"{$classLabel}\" introuvable (plusieurs établissements existent, crée-la d'abord manuellement).";
                    continue;
                }

                $newClass = $this->createClassFromLabel($classLabel, $schools->first());
                $classes->put($classKey, $newClass);
                $createdClasses[$classKey] = $classLabel;
            }

            $parsedDate = $this->parseImportDate($birthDate);

            if ($parsedDate === null) {
                $errors[] = "Ligne {$line} : date de naissance invalide (\"{$birthDate}\").";
                continue;
            }

            $date = $parsedDate->format('Y-m-d');

            $class = $classes->get($classKey);

            $student = Student::create([
                'school_id' => $class->school_id,
                'school_class_id' => $class->id,
                'last_name' => $lastName,
                'first_name' => $firstName,
                'birth_date' => $date,
                'parent_phone' => trim((string) ($row['phone'] ?? '')) ?: null,
                'status' => 'active',
            ]);

            foreach ($row['guardians'] ?? [] as $guardianData) {
                $guardianLast = trim((string) ($guardianData['last_name'] ?? ''));
                $guardianFirst = trim((string) ($guardianData['first_name'] ?? ''));

                if ($guardianLast === '' && $guardianFirst === '') {
                    continue;
                }

                $student->guardians()->create([
                    'last_name' => $guardianLast,
                    'first_name' => $guardianFirst,
                ]);
            }

            $createdStudents[] = $student;
            $imported++;
        }

        $status = "{$imported} élève(s) importé(s) avec succès.";
        if ($createdClasses) {
            $status .= ' Classe(s) créée(s) automatiquement : '.implode(', ', $createdClasses).'.';
        }
        if ($errors) {
            $status .= ' '.count($errors)." ligne(s) ignorée(s).";
        }

        if ($request->hasFile('photos') && $createdStudents) {
            $photoStatus = $this->assignPhotosByFilename($request->file('photos'), $createdStudents);
            $status .= ' '.$photoStatus['summary'];
            $errors = array_merge($errors, $photoStatus['warnings']);
        }

        return redirect()
            ->route('dashboard')
            ->with('status', $status)
            ->with('importErrors', $errors);
    }

    /**
     * Relie chaque photo à l'élève dont le nom (nom + prénom) apparaît dans le nom
     * du fichier — indépendamment de l'ordre de sélection, c'est la méthode la plus
     * sûre. Une photo qui ne correspond à aucun élève par son nom, ou qui
     * correspondrait à plusieurs élèves à la fois, n'est pas devinée au hasard : elle
     * est mise de côté. En repli, s'il reste autant de photos non reconnues que
     * d'élèves encore sans photo, elles sont attribuées dans l'ordre (même position
     * dans la liste d'élèves importés que dans la sélection de photos) — utile quand
     * les fichiers ne portent pas le nom de l'élève mais ont été sélectionnés dans le
     * même ordre que la liste.
     *
     * @param  \Illuminate\Http\UploadedFile[]  $photos
     * @param  Student[]  $students
     * @return array{summary: string, warnings: string[]}
     */
    private function assignPhotosByFilename(array $photos, array $students): array
    {
        $studentTokens = [];
        foreach ($students as $index => $student) {
            $studentTokens[$index] = [
                'last' => $this->nameTokens($student->last_name),
                'first' => $this->nameTokens($student->first_name),
            ];
        }

        $matchesByPhoto = [];
        foreach ($photos as $photoIndex => $photo) {
            $normalizedFilename = $this->normalizeForMatch(pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME));

            $matches = [];
            foreach ($studentTokens as $index => $tokens) {
                if ($this->tokensPresentIn($tokens['last'], $normalizedFilename) && $this->tokensPresentIn($tokens['first'], $normalizedFilename)) {
                    $matches[] = $index;
                }
            }

            $matchesByPhoto[$photoIndex] = $matches;
        }

        // Une photo ne compte comme "assignable" que si elle correspond à un seul élève,
        // et un élève ne reçoit une photo que si une seule photo lui correspond.
        $matchCountByStudent = [];
        foreach ($matchesByPhoto as $matches) {
            if (count($matches) === 1) {
                $studentIndex = $matches[0];
                $matchCountByStudent[$studentIndex] = ($matchCountByStudent[$studentIndex] ?? 0) + 1;
            }
        }

        $assignedByName = 0;
        $warnings = [];
        $assignedStudentIndexes = [];
        $unmatchedPhotoIndexes = [];

        foreach ($matchesByPhoto as $photoIndex => $matches) {
            $photo = $photos[$photoIndex];
            $filename = $photo->getClientOriginalName();

            if (count($matches) === 0) {
                $unmatchedPhotoIndexes[] = $photoIndex;

                continue;
            }

            if (count($matches) > 1) {
                $warnings[] = "Photo \"{$filename}\" : correspond à plusieurs élèves, ignorée pour éviter une erreur.";

                continue;
            }

            $studentIndex = $matches[0];

            if (($matchCountByStudent[$studentIndex] ?? 0) > 1) {
                $warnings[] = "Photo \"{$filename}\" : plusieurs photos correspondent au même élève, aucune n'a été attribuée.";

                continue;
            }

            $students[$studentIndex]->update([
                'photo_path' => $photo->store('students/photos', 'public'),
                'photo_position_x' => 50,
                'photo_position_y' => 50,
            ]);
            $assignedStudentIndexes[$studentIndex] = true;
            $assignedByName++;
        }

        // Repli par ordre : les photos dont le nom de fichier ne correspond à aucun
        // élève sont attribuées, dans l'ordre, aux élèves qui n'ont pas encore reçu
        // de photo — seulement si les deux listes ont la même taille, pour éviter un
        // décalage silencieux si un fichier a été oublié ou ajouté en trop.
        $studentsWithoutPhoto = array_values(array_diff(array_keys($students), array_keys($assignedStudentIndexes)));
        $assignedByOrder = 0;

        if ($unmatchedPhotoIndexes !== [] && count($unmatchedPhotoIndexes) === count($studentsWithoutPhoto)) {
            foreach ($unmatchedPhotoIndexes as $position => $photoIndex) {
                $photo = $photos[$photoIndex];
                $student = $students[$studentsWithoutPhoto[$position]];

                $student->update([
                    'photo_path' => $photo->store('students/photos', 'public'),
                    'photo_position_x' => 50,
                    'photo_position_y' => 50,
                ]);
                $assignedByOrder++;
            }
        } elseif ($unmatchedPhotoIndexes !== []) {
            foreach ($unmatchedPhotoIndexes as $photoIndex) {
                $filename = $photos[$photoIndex]->getClientOriginalName();
                $warnings[] = "Photo \"{$filename}\" : aucun élève correspondant trouvé par le nom, et le nombre de photos restantes (".count($unmatchedPhotoIndexes).") ne correspond pas au nombre d'élèves encore sans photo (".count($studentsWithoutPhoto)."), donc pas d'attribution par ordre non plus.";
            }
        }

        $assigned = $assignedByName + $assignedByOrder;
        $withoutPhoto = count($students) - $assigned;

        $summary = "{$assignedByName} photo(s) attribuée(s) par nom";
        if ($assignedByOrder > 0) {
            $summary .= " et {$assignedByOrder} par ordre";
        }
        $summary .= '.';
        if ($withoutPhoto > 0) {
            $summary .= " {$withoutPhoto} élève(s) sans photo.";
        }

        return ['summary' => $summary, 'warnings' => $warnings];
    }

    /**
     * Découpe un nom en "mots" comparables (accents retirés, minuscules), en
     * ignorant les particules d'un seul caractère qui donneraient de faux positifs.
     *
     * @return string[]
     */
    private function nameTokens(string $name): array
    {
        $normalized = $this->normalizeForMatch($name);

        return array_values(array_filter(explode(' ', $normalized), fn ($token) => mb_strlen($token) > 1));
    }

    private function tokensPresentIn(array $tokens, string $haystack): bool
    {
        if ($tokens === []) {
            return false;
        }

        foreach ($tokens as $token) {
            if (! str_contains($haystack, $token)) {
                return false;
            }
        }

        return true;
    }

    private function normalizeForMatch(string $value): string
    {
        $ascii = \Illuminate\Support\Str::ascii($value);
        $cleaned = preg_replace('/[^a-zA-Z0-9]+/', ' ', $ascii);

        return trim(mb_strtolower($cleaned));
    }

    /**
     * Lit un CSV avec les colonnes de self::IMPORT_HEADER (les colonnes
     * accompagnateur* et telephone sont optionnelles à la fin du fichier).
     * Retourne null si l'en-tête ne correspond pas au format attendu.
     *
     * @return array<int, array{line: int, last_name: string, first_name: string, birth_date: string, class_label: string, phone: string, guardians: array}>|null
     */
    /**
     * Devine le séparateur du CSV (virgule, point-virgule ou tabulation) à partir
     * de sa première ligne. Excel en français (et beaucoup de tableurs européens)
     * exporte en point-virgule plutôt qu'en virgule — sans cette détection, la
     * ligne d'en-tête ne correspond à aucune colonne connue et l'import est
     * rejeté à tort avec "le fichier CSV doit avoir les colonnes...".
     */
    private function detectCsvDelimiter($handle): string
    {
        $firstLine = fgets($handle);
        rewind($handle);

        $firstLine = (string) preg_replace('/^\xEF\xBB\xBF/', '', $firstLine ?: '');

        $best = ',';
        $bestCount = 0;

        foreach ([',', ';', "\t"] as $candidate) {
            $count = substr_count($firstLine, $candidate);
            if ($count > $bestCount) {
                $bestCount = $count;
                $best = $candidate;
            }
        }

        return $best;
    }

    private function extractRowsFromCsv(string $path): ?array
    {
        $handle = fopen($path, 'r');
        $delimiter = $this->detectCsvDelimiter($handle);
        $header = fgetcsv($handle, 0, $delimiter);

        if ($header) {
            // Retire un éventuel BOM UTF-8 sur la première colonne.
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
            $header = array_map(fn ($h) => strtolower(trim($h)), $header);
        }

        $isLegacyHeader = $header === ['nom', 'prenom', 'date_naissance', 'classe'];
        $isFullHeader = $header === self::IMPORT_HEADER;

        if (! $isLegacyHeader && ! $isFullHeader) {
            fclose($handle);

            return null;
        }

        $columnCount = $isFullHeader ? 11 : 4;
        $rows = [];
        $line = 1;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $line++;

            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue; // ligne vide
            }

            $row = array_pad($row, $columnCount, null);
            [$lastName, $firstName, $birthDate, $classLabel] = $row;

            $guardians = [];
            if ($isFullHeader) {
                $phone = $row[4];
                for ($g = 0; $g < 3; $g++) {
                    $guardians[] = [
                        'last_name' => $row[5 + $g * 2],
                        'first_name' => $row[6 + $g * 2],
                    ];
                }
            }

            $rows[] = [
                'line' => $line,
                'last_name' => (string) $lastName,
                'first_name' => (string) $firstName,
                'birth_date' => (string) $birthDate,
                'class_label' => (string) $classLabel,
                'phone' => (string) ($phone ?? ''),
                'guardians' => $guardians,
            ];
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Extrait un tableau d'élèves depuis un PDF. Essaie d'abord le format officiel
     * "Liste des élèves" (tableau N°/Matricule/Nom/Prénom(s)/Sexe/Date de naissance/
     * Lieu de naissance, avec une seule ligne "Classe : X" pour tout le document),
     * puis retombe sur l'ancienne heuristique ligne par ligne pour les autres PDF.
     *
     * @return array<int, array{line: int, last_name: string, first_name: string, birth_date: string, class_label: string}>
     */
    private function extractRowsFromPdf(string $path): array
    {
        $fullText = (new PdfParser())->parseFile($path)->getText();

        if (preg_match('/Classe\s*:\s*(.+)/u', $fullText, $classMatch)) {
            $officialRows = $this->extractRowsFromOfficialTable($fullText, trim($classMatch[1]));

            if ($officialRows !== []) {
                return $officialRows;
            }
        }

        $classLabels = SchoolClass::all()
            ->map(fn ($c) => trim($c->level.' '.$c->section))
            ->sortByDesc(fn ($label) => strlen($label))
            ->values();

        $datePattern = '/\b(\d{4}-\d{1,2}-\d{1,2}|\d{1,2}[\/\-.]\d{1,2}[\/\-.]\d{4})\b/';

        $rows = [];
        $line = 0;

        foreach (preg_split('/\r\n|\r|\n/', $fullText) as $rawLine) {
            $line++;
            $text = trim($rawLine);

            if ($text === '' || ! preg_match($datePattern, $text)) {
                continue; // pas une ligne élève reconnaissable (titre, en-tête, ligne vide...)
            }

            if (preg_match('/^(nom|classe|liste)/i', $text)) {
                continue; // en-tête de tableau
            }

            preg_match($datePattern, $text, $dateMatch);
            $birthDate = $dateMatch[1];
            $remainder = trim(str_replace($birthDate, ' ', $text));

            $phone = '';
            if (preg_match('/(\+?\d[\d\s\-]{7,14}\d)/', $remainder, $phoneMatch)) {
                $phone = trim($phoneMatch[1]);
                $remainder = trim(str_replace($phoneMatch[1], ' ', $remainder));
            }

            $classLabel = $this->matchClassLabel($remainder, $classLabels);
            if ($classLabel === null) {
                $rows[] = ['line' => $line, 'last_name' => '', 'first_name' => '', 'birth_date' => $birthDate, 'class_label' => '', 'phone' => $phone, 'guardians' => []];
                continue;
            }

            $namePart = trim(preg_replace('/\s+/', ' ', str_ireplace($classLabel, ' ', $remainder)));
            [$lastName, $firstName] = $this->splitName($namePart);

            $rows[] = [
                'line' => $line,
                'last_name' => $lastName,
                'first_name' => $firstName,
                'birth_date' => $birthDate,
                'class_label' => $classLabel,
                'phone' => $phone,
                'guardians' => [],
            ];
        }

        return $rows;
    }

    /**
     * Format officiel béninois (ex: export MESTFP "Liste des élèves") : chaque ligne
     * d'élève est "N° Matricule NOM Prénom(s) Sexe JJ/MM/AAAA Lieu de naissance",
     * la classe étant déclarée une seule fois pour tout le document plutôt que
     * répétée sur chaque ligne — c'est ce qui empêchait l'ancien parseur de la
     * reconnaître (il cherchait le nom de classe sur la ligne de l'élève).
     *
     * @return array<int, array{line: int, last_name: string, first_name: string, birth_date: string, class_label: string, phone: string, guardians: array}>
     */
    private function extractRowsFromOfficialTable(string $fullText, string $classLabel): array
    {
        // Le texte brut extrait du PDF perd une partie des espaces entre colonnes
        // (numéro de ligne et matricule collés au nom, sexe collé à la date...) et
        // le séparateur avant le sexe varie (tabulation ou espace selon la ligne) :
        // on repère donc directement "M" ou "F" collé à une date JJ/MM/AAAA, seul
        // point du texte où ce motif apparaît, plutôt que de compter sur des espaces.
        $rowPattern = '/^\d+\s*(.+?)\s*([MF])(\d{1,2}\/\d{1,2}\/\d{4})\s+(.+)$/u';

        $rows = [];
        $line = 0;

        foreach (preg_split('/\r\n|\r|\n/', $fullText) as $rawLine) {
            $line++;
            $text = trim($rawLine);

            if ($text === '' || ! preg_match($rowPattern, $text, $m)) {
                continue;
            }

            $namePart = trim(preg_replace('/\s+/', ' ', $m[1]));
            [$lastName, $firstName] = $this->splitName($namePart);

            if ($lastName === '' && $firstName === '') {
                continue;
            }

            $rows[] = [
                'line' => $line,
                'last_name' => $lastName,
                'first_name' => $firstName,
                'birth_date' => $m[3],
                'class_label' => $classLabel,
                'phone' => '',
                'guardians' => [],
            ];
        }

        return $rows;
    }

    /**
     * Crée une classe à partir d'un intitulé importé (ex: "4e A") non reconnu parmi
     * les classes existantes. La dernière "lettre" isolée est prise comme section
     * (convention déjà utilisée dans l'appli, ex: "CM1 A" = niveau "CM1" + section "A") ;
     * à défaut, tout l'intitulé devient le niveau, sans section.
     */
    private function createClassFromLabel(string $label, School $school): SchoolClass
    {
        $tokens = preg_split('/\s+/', trim($label), flags: PREG_SPLIT_NO_EMPTY);
        $section = null;

        if (count($tokens) > 1 && preg_match('/^[A-Za-z]$/u', end($tokens))) {
            $section = mb_strtoupper(array_pop($tokens));
        }

        return SchoolClass::create([
            'school_id' => $school->id,
            'level' => implode(' ', $tokens),
            'section' => $section,
        ]);
    }

    /**
     * Interprète une date de naissance importée en forçant le format JJ/MM/AAAA
     * quand elle est écrite avec des barres obliques (CSV/PDF francophones), plutôt
     * que Carbon::parse() seul : celui-ci suppose par défaut MOIS/JOUR/ANNÉE à
     * l'américaine, ce qui inverse silencieusement jour et mois (ex: "06/10/2013"
     * devenait le 10 juin au lieu du 6 octobre), ou plante dès que le jour dépasse 12
     * (ex: "26/08/2012" jugé invalide car 26 n'est pas un mois).
     */
    private function parseImportDate(string $value): ?\Carbon\Carbon
    {
        $value = trim($value);

        if (preg_match('#^\d{1,2}/\d{1,2}/\d{4}$#', $value)) {
            foreach (['d/m/Y', 'j/n/Y'] as $format) {
                try {
                    return \Carbon\Carbon::createFromFormat('!'.$format, $value);
                } catch (\Throwable) {
                    continue;
                }
            }

            return null;
        }

        try {
            return \Carbon\Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function matchClassLabel(string $text, Collection $classLabels): ?string
    {
        foreach ($classLabels as $label) {
            if (preg_match('/\b'.preg_quote($label, '/').'\b/i', $text)) {
                return $label;
            }
        }

        return null;
    }

    /**
     * Sépare "NOM Prénom(s)" en respectant la convention nom en majuscules.
     * À défaut, le premier mot est pris comme nom.
     *
     * @return array{0: string, 1: string}
     */
    private function splitName(string $namePart): array
    {
        $tokens = preg_split('/\s+/', trim($namePart), flags: PREG_SPLIT_NO_EMPTY);

        if (count($tokens) < 2) {
            return ['', $tokens[0] ?? ''];
        }

        $lastNameTokens = [];
        $i = 0;
        while ($i < count($tokens) - 1 && $tokens[$i] === mb_strtoupper($tokens[$i], 'UTF-8') && mb_strlen($tokens[$i]) > 1) {
            $lastNameTokens[] = $tokens[$i];
            $i++;
        }

        if ($lastNameTokens === []) {
            $lastNameTokens[] = $tokens[0];
            $i = 1;
        }

        $firstName = implode(' ', array_slice($tokens, $i));

        return [implode(' ', $lastNameTokens), $firstName];
    }

    private function validateStudent(Request $request, ?Student $student = null): array
    {
        return $request->validate([
            'last_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'birth_date' => ['required', 'date'],
            'school_class_id' => ['required', 'exists:school_classes,id'],
            'parent_phone' => ['nullable', 'string', 'max:50'],
            'photo' => [$student ? 'nullable' : 'required', 'image', 'max:15360'],
            'photo_position_x' => ['nullable', 'integer', 'min:0', 'max:100'],
            'photo_position_y' => ['nullable', 'integer', 'min:0', 'max:100'],
            'guardians' => ['array', 'max:3'],
            'guardians.*.first_name' => ['nullable', 'string', 'max:255'],
            'guardians.*.last_name' => ['nullable', 'string', 'max:255'],
            'guardians.*.photo' => ['nullable', 'image', 'max:15360'],
            'guardians.*.photo_position_x' => ['nullable', 'integer', 'min:0', 'max:100'],
            'guardians.*.photo_position_y' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);
    }

    /**
     * Crée ou met à jour les accompagnateurs en fonction de leur position dans le formulaire.
     * N'agit que si le formulaire soumis contient effectivement des champs "guardians",
     * afin qu'un formulaire partiel (ex: changement de classe seul) ne les efface pas.
     */
    private function saveGuardians(Request $request, Student $student, bool $isUpdate = false): void
    {
        if (! $request->has('guardians')) {
            return;
        }

        $guardiansInput = $request->input('guardians', []);
        $photos = $request->file('guardians', []);
        $existing = $isUpdate ? $student->guardians()->orderBy('id')->get()->values() : collect();

        foreach ($guardiansInput as $index => $guardianData) {
            $firstName = trim($guardianData['first_name'] ?? '');
            $lastName = trim($guardianData['last_name'] ?? '');
            $photo = $photos[$index]['photo'] ?? null;
            $existingGuardian = $existing->get($index);

            if ($firstName === '' && $lastName === '' && ! $photo) {
                continue;
            }

            $photoPath = $existingGuardian?->photo_path;
            if ($photo) {
                if ($photoPath) {
                    Storage::disk('public')->delete($photoPath);
                }
                $photoPath = $photo->store('guardians/photos', 'public');
            }

            $attrs = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'photo_path' => $photoPath,
                'photo_position_x' => $guardianData['photo_position_x'] ?? 50,
                'photo_position_y' => $guardianData['photo_position_y'] ?? 50,
            ];

            if ($existingGuardian) {
                $existingGuardian->update($attrs);
            } else {
                $student->guardians()->create($attrs);
            }
        }
    }
}
