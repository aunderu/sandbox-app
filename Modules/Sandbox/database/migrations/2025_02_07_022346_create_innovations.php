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
        Schema::create('innovations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('school_name');
            $table->string('inno_name');
            $table->unsignedBigInteger('inno_type_id');
            $table->text('inno_description');
            $table->string('attachments')->nullable();
            $table->string('video_url')->nullable();
            $table->json('tags');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->nullable();

            $table->foreign('inno_type_id')->references('id')->on('innovation_types')->onDelete('cascade');
            $table->foreign('school_id')->references('school_id')->on('school_data')->onDelete('cascade');
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
