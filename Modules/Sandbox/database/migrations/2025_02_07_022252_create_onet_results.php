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
        Schema::create('onet_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('school_year_id'); // ปีการศึกษา
            $table->unsignedBigInteger('grade_id'); // ระดับชั้น
            $table->decimal('math_score', 5, 2)->nullable();
            $table->decimal('thai_score', 5, 2)->nullable();
            $table->decimal('english_score', 5, 2)->nullable();
            $table->decimal('science_score', 5, 2)->nullable();
            $table->decimal('total_average', 5, 2)->nullable();
            $table->timestamps();

            $table->foreign('school_id')->references('school_id')->on('school_data')->onDelete('cascade');
            $table->foreign('school_year_id')->references('id')->on('school_years')->onDelete('cascade');
            $table->foreign('grade_id')->references('id')->on('grade_levels')->onDelete('cascade');

            // ป้องกันข้อมูลซ้ำ (โรงเรียน + ปีการศึกษา + ระดับชั้น)
            $table->unique(['school_id', 'school_year_id', 'grade_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('onet_results');
    }
};
