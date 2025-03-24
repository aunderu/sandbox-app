<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('core_competency_assessments', function (Blueprint $table) {
            $table->string('id', 40)->primary();
            $table->unsignedBigInteger('school_id');
            $table->integer('education_year');
            $table->decimal('self_management_score', 8, 2);
            $table->decimal('teamwork_score', 8, 2);
            $table->decimal('high_thinking_score', 8, 2);
            $table->decimal('communication_score', 8, 2);
            $table->decimal('active_citizen_score', 8, 2);
            $table->decimal('sustainable_coexistence_score', 8, 2);
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
        Schema::dropIfExists('core_competency_assessments');
    }
};
