<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('basic_subject_assessments', function (Blueprint $table) {
            $table->string('id', 20)->primary(); // รหัสที่มีรูปแบบเฉพาะ
            $table->string('school_id', 15); // รหัสโรงเรียน
            $table->string('education_year', 4); // ปีการศึกษา
            $table->decimal('thai_score', 5, 2)->nullable(); // คะแนนวิชาภาษาไทย
            $table->decimal('math_score', 5, 2)->nullable(); // คะแนนวิชาคณิตศาสตร์
            $table->decimal('science_score', 5, 2)->nullable(); // คะแนนวิชาวิทยาศาสตร์
            $table->decimal('english_score', 5, 2)->nullable(); // คะแนนวิชาอังกฤษ
            $table->timestamps();
            
            // Foreign key constraint
            $table->foreign('school_id')->references('school_id')->on('school_data');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('basic_subject_assessments');
    }
};
