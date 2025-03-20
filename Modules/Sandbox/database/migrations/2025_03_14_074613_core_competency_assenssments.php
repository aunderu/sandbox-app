<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_competency_assessments', function (Blueprint $table) {
            $table->string('id', 20)->primary(); // รหัสที่มีรูปแบบเฉพาะ
            $table->string('school_id', 15); // รหัสโรงเรียน
            $table->string('education_year', 4); // ปีการศึกษา
            $table->decimal('self_management_score', 5, 2)->nullable(); // คะแนนสมรรถนะการจัดการตนเอง
            $table->decimal('teamwork_score', 5, 2)->nullable(); // คะแนนสมรรถนะการรวมพลังทำงานเป็นทีม
            $table->decimal('high_thinking_score', 5, 2)->nullable(); // คะแนนสมรรถนะการคิดขั้นสูง
            $table->decimal('communication_score', 5, 2)->nullable(); // คะแนนสมรรถนะการสั่งการ
            $table->decimal('active_citizen_score', 5, 2)->nullable(); // คะแนนสมรรถนะการเป็นพลเมืองที่เข้มแข็ง
            $table->decimal('sustainable_coexistence_score', 5, 2)->nullable(); // คะแนนสมรรถนะการอยู่ร่วมกับธรรมชาติและวิทยาศาสตร์อย่างยั่งยืน
            $table->timestamps();
            
            // Foreign key constraint
            $table->foreign('school_id')->references('school_id')->on('school_data');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_competency_assessments');
    }
};
