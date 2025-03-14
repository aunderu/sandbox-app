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
        Schema::create('student_number', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('grade_id');
            $table->integer('male_count')->nullable();
            $table->integer('female_count')->nullable();
            $table->timestamps();

            // Set up foreign key constraint
            // $table->foreign('school_id')->references('school_id')->on('school_models')->onDelete('cascade');
            // $table->foreign('grade_id')->references('grade_id')->on('academic_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_number');
    }
};
