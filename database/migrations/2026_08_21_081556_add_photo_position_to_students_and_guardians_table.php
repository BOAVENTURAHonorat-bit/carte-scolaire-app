<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->unsignedTinyInteger('photo_position_x')->default(50)->after('photo_path');
            $table->unsignedTinyInteger('photo_position_y')->default(50)->after('photo_position_x');
        });

        Schema::table('student_guardians', function (Blueprint $table) {
            $table->unsignedTinyInteger('photo_position_x')->default(50)->after('photo_path');
            $table->unsignedTinyInteger('photo_position_y')->default(50)->after('photo_position_x');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['photo_position_x', 'photo_position_y']);
        });

        Schema::table('student_guardians', function (Blueprint $table) {
            $table->dropColumn(['photo_position_x', 'photo_position_y']);
        });
    }
};
