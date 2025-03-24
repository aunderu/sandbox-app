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
            $table->id('school_id');
            $table->string('school_name_th');
            $table->string('school_name_en')->nullable();
            $table->string('ministry')->nullable();
            $table->string('department')->nullable();
            $table->string('area')->nullable();
            $table->string('school_sizes')->nullable();
            $table->date('founding_date')->nullable();
            $table->text('school_course_type')->nullable();
            $table->text('course_attachment')->nullable();
            $table->string('original_filename')->nullable();
            $table->enum('principal_prefix_code', ['นาย', 'นาง', 'นางสาว', ''])->nullable();
            $table->string('principal_name_thai', 100);
            $table->string('principal_middle_name_thai', 100)->nullable();
            $table->string('principal_lastname_thai', 100);
            $table->enum('deputy_principal_prefix_code', ['นาย', 'นาง', 'นางสาว', ''])->nullable();
            $table->string('deputy_principal_name_thai', 100)->nullable();
            $table->string('deputy_principal_middle_name_thai', 100)->nullable();
            $table->string('deputy_principal_lastname_thai', 100)->nullable();
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
            $table->integer('student_amount')->default(0);
            $table->integer('disadvantaged_student_amount')->default(0);
            $table->integer('teacher_amount')->default(0);
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
