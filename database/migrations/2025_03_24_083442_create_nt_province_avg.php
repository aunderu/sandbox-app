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
        Schema::create('nt_province_avg', function (Blueprint $table) {
            $table->unsignedBigInteger('school_year_id');
            $table->unsignedBigInteger('grade_id');
            $table->decimal('math_avg', 5, 2)->nullable();
            $table->decimal('thai_avg', 5, 2)->nullable();
            $table->decimal('total_avg', 5, 2)->nullable();
            $table->timestamps();

            $table->primary(['school_year_id', 'grade_id']);

            $table->foreign('grade_id')
                ->references('id')
                ->on('grade_levels')
                ->onDelete('cascade')
                ->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nt_province_avg');
    }
};
