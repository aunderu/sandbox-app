<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('innovations', function (Blueprint $table) {
            $table->string('innovationID', 16)->primary();
            $table->integer('education_year')->nullable();
            $table->enum('semester', ['0', '1', '2', ''])->default('0');
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('user_id')->default(1);
            $table->unsignedBigInteger('inno_type_id');
            $table->string('inno_name');
            $table->longText('inno_description');
            $table->string('attachments')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('video_url')->nullable();
            $table->json('tags')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable();

            $table->foreign('school_id')
                ->references('school_id')
                ->on('school_data')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('inno_type_id')
                ->references('id')
                ->on('innovation_types')
                ->onDelete('restrict')
                ->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('innovations');
    }
};
