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
        Schema::create('onet_province_avg', function (Blueprint $table) {
            $table->unsignedBigInteger('school_year_id');
            $table->unsignedBigInteger('year_id');
            $table->decimal('math_avg', 5, 2)->nullable();
            $table->decimal('thai_avg', 5, 2)->nullable();
            $table->decimal('english_avg', 5, 2)->nullable();
            $table->decimal('science_avg', 5, 2)->nullable();
            $table->decimal('total_avg', 5, 2)->nullable();
            $table->timestamps();

            $table->foreign('school_year_id')->references('id')->on('school_years')->onDelete('cascade');
            $table->foreign('grade_id')->references('id')->on('grade_levels')->onDelete('cascade');

            // ป้องกันข้อมูลซ้ำ (ปีการศึกษา + ระดับชั้น)
            $table->primary(['school_year_id', 'grade_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('onet_province_avg');
    }
};
