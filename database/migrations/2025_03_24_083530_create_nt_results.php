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
        Schema::create('nt_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->unsignedBigInteger('school_year_id')->nullable();
            $table->unsignedBigInteger('grade_id')->nullable();
            $table->decimal('math_score', 5, 2)->nullable();
            $table->decimal('thai_score', 5, 2)->nullable();
            $table->decimal('total_average', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'school_year_id', 'grade_id']);

            $table->foreign('school_id')
                ->references('school_id')
                ->on('school_data')
                ->onDelete('cascade');

            $table->foreign('grade_id')
                ->references('id')
                ->on('grade_levels')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nt_results');
    }
};
