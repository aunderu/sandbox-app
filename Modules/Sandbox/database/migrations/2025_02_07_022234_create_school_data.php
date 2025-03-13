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
        Schema::create('school_data', function (Blueprint $table) {
            $table->id('school_id')->primary();
            $table->string('school_name_th');
            $table->string('school_name_en')->nullable();
            $table->string('ministry')->nullable();
            $table->string('department')->nullable();
            $table->string('area')->nullable();
            $table->string('school_sizes')->nullable();
            $table->date('founding_date')->nullable();
            $table->json('school_course_type')->nullable();
            $table->string('house_id')->nullable();
            $table->string('village_no')->nullable();
            $table->string('road')->nullable();
            $table->string('sub_district')->nullable();
            $table->string('district')->nullable();
            $table->string('province')->nullable();
            $table->string('phone')->nullable();
            $table->string('fax')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->integer('student_amount');
            $table->integer('disadvantaged_student_amount');
            $table->integer('teacher_amount');
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        schema::dropIfExists('school_data');
    }
};
