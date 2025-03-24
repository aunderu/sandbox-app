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
            $table->unsignedBigInteger('school_year_id');
            $table->unsignedBigInteger('grade_id');
            $table->decimal('math_score', 5, 2)->nullable();
            $table->decimal('thai_score', 5, 2)->nullable();
            $table->decimal('english_score', 5, 2)->nullable();
            $table->decimal('science_score', 5, 2)->nullable();
            $table->decimal('social_score', 5, 2)->nullable();
            $table->decimal('total_average', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'school_year_id', 'grade_id'], 'unique_school_year_grade');

            $table->foreign('school_id')
                ->references('school_id')
                ->on('school_data')
                ->onDelete('cascade')
                ->onUpdate('restrict');

            $table->foreign('grade_id')
                ->references('id')
                ->on('grade_levels')
                ->onDelete('restrict')
                ->onUpdate('restrict');
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
