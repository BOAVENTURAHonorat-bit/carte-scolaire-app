<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentCardWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_landing_and_apercu_are_accessible_without_login(): void
    {
        $this->get('/')->assertOk()->assertSee('Se connecter');
        $this->get('/apercu')->assertOk()->assertSee('KOUASSI');
    }

    public function test_secretaire_can_create_a_student_with_photos_and_guardians(): void
    {
        Storage::fake('public');

        $school = School::create(['name' => 'École Test']);
        $class = SchoolClass::create(['school_id' => $school->id, 'level' => 'Grande Section', 'section' => 'A']);
        $secretaire = User::factory()->create(['role' => 'secretaire']);

        $response = $this->actingAs($secretaire)->post('/students', [
            'last_name' => 'TRAORE',
            'first_name' => 'Fatou',
            'birth_date' => '2020-03-15',
            'school_class_id' => $class->id,
            'photo' => UploadedFile::fake()->image('eleve.jpg'),
            'guardians' => [
                ['first_name' => 'Awa', 'last_name' => 'TRAORE', 'relation' => 'Mère', 'phone' => '0102030405', 'photo' => UploadedFile::fake()->image('mere.jpg')],
                ['first_name' => '', 'last_name' => '', 'relation' => '', 'phone' => ''],
                ['first_name' => '', 'last_name' => '', 'relation' => '', 'phone' => ''],
            ],
        ]);

        $response->assertRedirect(route('students.create'));
        $student = Student::where('last_name', 'TRAORE')->first();
        $this->assertNotNull($student);
        $this->assertSame($class->id, $student->school_class_id);
        $this->assertSame($school->id, $student->school_id);
        Storage::disk('public')->assertExists($student->photo_path);
        $this->assertCount(1, $student->guardians);
        Storage::disk('public')->assertExists($student->guardians->first()->photo_path);
    }

    public function test_secretaire_cannot_access_dg_pages(): void
    {
        $secretaire = User::factory()->create(['role' => 'secretaire']);

        $this->actingAs($secretaire)->get('/students')->assertForbidden();
    }

    public function test_dg_can_move_a_student_to_another_class_without_losing_guardians(): void
    {
        Storage::fake('public');

        $school = School::create(['name' => 'École Test']);
        $classA = SchoolClass::create(['school_id' => $school->id, 'level' => 'Petite Section', 'section' => 'A']);
        $classB = SchoolClass::create(['school_id' => $school->id, 'level' => 'Moyenne Section', 'section' => 'B']);

        $student = Student::create([
            'school_id' => $school->id,
            'school_class_id' => $classA->id,
            'last_name' => 'KONE',
            'first_name' => 'Ali',
            'birth_date' => '2019-01-01',
        ]);
        $student->guardians()->create([
            'first_name' => 'Moussa', 'last_name' => 'KONE', 'relation' => 'Père', 'photo_path' => 'guardians/photos/fake.jpg',
        ]);

        $dg = User::factory()->create(['role' => 'dg']);

        $response = $this->actingAs($dg)->put("/students/{$student->id}", [
            'last_name' => $student->last_name,
            'first_name' => $student->first_name,
            'birth_date' => $student->birth_date->format('Y-m-d'),
            'school_class_id' => $classB->id,
        ]);

        $response->assertRedirect(route('students.index'));
        $student->refresh();
        $this->assertSame($classB->id, $student->school_class_id);
        $this->assertCount(1, $student->guardians()->get());
    }

    public function test_classes_index_lists_classes_and_pdf_export_works_for_a_full_class(): void
    {
        $school = School::create(['name' => 'École Test']);
        $classA = SchoolClass::create(['school_id' => $school->id, 'level' => 'Maternelle 1', 'section' => 'A']);
        $classB = SchoolClass::create(['school_id' => $school->id, 'level' => 'Maternelle 1', 'section' => 'B']);

        Student::create([
            'school_id' => $school->id, 'school_class_id' => $classA->id,
            'last_name' => 'KONE', 'first_name' => 'Ali', 'birth_date' => '2019-01-01',
        ]);
        Student::create([
            'school_id' => $school->id, 'school_class_id' => $classA->id,
            'last_name' => 'DIALLO', 'first_name' => 'Mariam', 'birth_date' => '2019-06-01',
        ]);

        $dg = User::factory()->create(['role' => 'dg']);

        $this->actingAs($dg)->get('/classes')
            ->assertOk()
            ->assertSee('Maternelle 1 A')
            ->assertSee('Maternelle 1 B')
            ->assertSee('Aucun élève');

        $response = $this->actingAs($dg)->get(route('classes.cards', $classA));
        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_school_cards_export_combines_all_classes_without_mixing_schools(): void
    {
        $schoolA = School::create(['name' => 'Père Gilbert']);
        $classA1 = SchoolClass::create(['school_id' => $schoolA->id, 'level' => 'Maternelle 1', 'section' => 'A']);
        $classA2 = SchoolClass::create(['school_id' => $schoolA->id, 'level' => 'Maternelle 2', 'section' => 'A']);

        $schoolB = School::create(['name' => 'Une Autre École']);
        $classB1 = SchoolClass::create(['school_id' => $schoolB->id, 'level' => 'Maternelle 1', 'section' => 'A']);

        Student::create(['school_id' => $schoolA->id, 'school_class_id' => $classA1->id, 'last_name' => 'KONE', 'first_name' => 'Ali', 'birth_date' => '2019-01-01']);
        Student::create(['school_id' => $schoolA->id, 'school_class_id' => $classA2->id, 'last_name' => 'DIALLO', 'first_name' => 'Mariam', 'birth_date' => '2018-01-01']);
        Student::create(['school_id' => $schoolB->id, 'school_class_id' => $classB1->id, 'last_name' => 'AUTRE', 'first_name' => 'Eleve', 'birth_date' => '2019-01-01']);

        $dg = User::factory()->create(['role' => 'dg']);

        // La page groupe bien les classes par école, avec un total distinct par école.
        $this->actingAs($dg)->get('/classes')
            ->assertOk()
            ->assertSee('Père Gilbert')
            ->assertSee('Une Autre École')
            ->assertSeeInOrder(['Père Gilbert', '2 élèves', 'Une Autre École', '1 élève']);

        $response = $this->actingAs($dg)->get(route('schools.cards', $schoolA));
        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));

        // Vérifie que le PDF ne contient que les 2 élèves de l'école A (pas celui de l'école B).
        $html = view('cards.maternelle', [
            'students' => Student::where('school_id', $schoolA->id)->get(),
            'school' => $schoolA,
        ])->render();
        $this->assertStringContainsString('KONE', $html);
        $this->assertStringContainsString('DIALLO', $html);
        $this->assertStringNotContainsString('AUTRE', $html);
    }

    public function test_dg_can_delete_a_student_card(): void
    {
        $school = School::create(['name' => 'École Test']);
        $class = SchoolClass::create(['school_id' => $school->id, 'level' => 'Petite Section', 'section' => 'A']);
        $student = Student::create([
            'school_id' => $school->id,
            'school_class_id' => $class->id,
            'last_name' => 'KONE',
            'first_name' => 'Ali',
            'birth_date' => '2019-01-01',
        ]);

        $dg = User::factory()->create(['role' => 'dg']);

        $this->actingAs($dg)->delete("/students/{$student->id}")->assertRedirect(route('students.index'));
        $this->assertDatabaseMissing('students', ['id' => $student->id]);
    }
}
