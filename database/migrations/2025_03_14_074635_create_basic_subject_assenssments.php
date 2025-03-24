<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('basic_subject_assessments', function (Blueprint $table) {
            $table->string('id', 40)->primary();
            $table->unsignedBigInteger('school_id');
            $table->integer('education_year');
            $table->decimal('thai_score', 8, 2);
            $table->decimal('math_score', 8, 2);
            $table->decimal('science_score', 8, 2);
            $table->decimal('english_score', 8, 2);
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('school_id')
                ->references('school_id')
                ->on('school_data')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('basic_subject_assessments');
    }
};
