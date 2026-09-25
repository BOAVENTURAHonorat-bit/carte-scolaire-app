<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('country')->nullable();
            $table->string('school_type')->nullable();
            $table->string('name');
            $table->string('slogan')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('representative_photo_path')->nullable();
            $table->string('primary_color')->default('#1E3A8A');
            $table->string('secondary_color')->default('#F59E0B');
            $table->string('exit_hours')->nullable();
            $table->string('late_penalty_note')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
