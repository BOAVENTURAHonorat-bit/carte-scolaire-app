<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentCardController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/apercu', [PageController::class, 'apercu'])->name('apercu');

Route::get('/dashboard', [StudentController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/students', [StudentController::class, 'index'])->name('students.index');

    Route::get('/students/{student}/card', [StudentCardController::class, 'show'])->name('students.card');
    Route::get('/students/{student}/card/preview', [StudentCardController::class, 'preview'])->name('students.card.preview');
    Route::get('/classes', [StudentCardController::class, 'classesIndex'])->name('classes.index');
    Route::get('/classes/{schoolClass}/cards', [StudentCardController::class, 'classCards'])->name('classes.cards');
    Route::get('/classes/{schoolClass}/cards/preview', [StudentCardController::class, 'classPreview'])->name('classes.cards.preview');

    // Import de classe (CSV/PDF + photos) : ouvert à tous les comptes connectés,
    // quel que soit leur rôle.
    Route::get('/students/import/template', [StudentController::class, 'importTemplate'])->name('students.import.template');
    Route::post('/students/import', [StudentController::class, 'import'])->name('students.import');

    Route::middleware('role:secretaire,dg,dev')->group(function () {
        Route::get('/classes/{schoolClass}/photos/bulk', [StudentController::class, 'bulkPhotosForm'])->name('students.photos.bulk-form');
        Route::post('/classes/{schoolClass}/photos/bulk', [StudentController::class, 'bulkPhotosStore'])->name('students.photos.bulk-store');
    });

    Route::middleware('role:secretaire,dg,dev')->group(function () {
        Route::get('/students/create', [StudentController::class, 'create'])->name('students.create');
        Route::post('/students/preview-card', [StudentController::class, 'previewDraft'])->name('students.preview-card');
        Route::post('/students', [StudentController::class, 'store'])->name('students.store');
        Route::get('/students/{student}/edit', [StudentController::class, 'edit'])->name('students.edit');
        Route::put('/students/{student}', [StudentController::class, 'update'])->name('students.update');
        Route::patch('/students/{student}/photo-position', [StudentController::class, 'updatePhotoPosition'])->name('students.photo-position');
    });

    Route::middleware('role:dg,dev')->group(function () {
        Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
        Route::patch('/students/{student}/toggle-status', [StudentController::class, 'toggleStatus'])->name('students.toggle-status');
        Route::get('/admins', [AdminController::class, 'index'])->name('admins.index');
        Route::get('/admins/create', [AdminController::class, 'create'])->name('admins.create');
        Route::post('/admins', [AdminController::class, 'store'])->name('admins.store');
        Route::get('/admins/{user}/edit', [AdminController::class, 'edit'])->name('admins.edit');
        Route::put('/admins/{user}', [AdminController::class, 'update'])->name('admins.update');
        Route::delete('/admins/{user}', [AdminController::class, 'destroy'])->name('admins.destroy');
    });

    // Réinitialisation de mot de passe des comptes : réservée au dev, même pas au DG.
    Route::middleware('role:dev')->group(function () {
        Route::post('/admins/{user}/reset-password', [AdminController::class, 'resetPassword'])->name('admins.reset-password');
    });
});

require __DIR__.'/auth.php';
