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
        Schema::create('participants', function (Blueprint $table) {
            $table->string('participant_id', 10)->primary()->comment('รหัสการเข้าร่วม: 1=ประเภทภาค, 2-3=พื้นที่นวัตกรรม, 4-5=ปี พ.ศ., 6-10=ลำดับ');
            $table->unsignedBigInteger('cooperation_school_id')->comment('รหัสสถานศึกษาที่เข้าไปมีส่วนร่วม');
            $table->unsignedBigInteger('user_id')->comment('รหัสผู้ใช้ที่เพิ่มข้อมูล');
            $table->string('participant_name')->comment('ชื่อภาครัฐหรือเอกชนที่เข้ามามีส่วนร่วม');
            $table->string('participant_type_code', 2)->comment('รหัสประเภทผู้เข้ามามีส่วนร่วม: 01=บุคคล, 02=หน่วยงานรัฐ/รัฐวิสาหกิจ, 03=บริษัทเอกชน, 04=มูลนิธิ, 05=สมาคม, 06=องค์กรต่างประเทศ');
            $table->string('contact_name')->nullable()->comment('ชื่อของผู้ติดต่อ');
            $table->string('contact_phone', 20)->nullable()->comment('เบอร์ผู้ติดต่อ');
            $table->string('contact_mobile_phone', 20)->nullable()->comment('เบอร์โทรศัพท์เคลื่อนที่');
            $table->string('contact_email')->nullable()->comment('อีเมลของผู้ติดต่อ');
            $table->string('contact_organization_position')->nullable()->comment('ตำแหน่งในองค์กรของผู้ติดต่อ');
            $table->date('cooperation_start_date')->comment('วันที่เริ่มมีส่วนร่วม');
            $table->date('cooperation_end_date')->nullable()->comment('วันที่สิ้นสุดการมีส่วนร่วม');
            $table->string('cooperation_status_code', 2)->default('01')->comment('รหัสสถานะการมีส่วนร่วม: 01=ยังมีส่วนร่วม, 02=สิ้นสุดการมีส่วนร่วม, 03=ไม่มีการเข้ามามีส่วนร่วม');
            $table->text('cooperation_activity')->nullable()->comment('กิจกรรมที่มีส่วนร่วม');
            $table->string('cooperation_level_code', 2)->comment('รหัสระดับการมีส่วนร่วม: 01=ให้ข้อมูล, 02=ให้คำปรึกษา, 03=มีส่วนร่วมบางส่วน, 04=ทำงานร่วมกัน, 05=สนับสนุนงบประมาณ, 06=สนับสนุนสื่อ/อุปกรณ์');
            $table->json('cooperation_attachment_url')->nullable()->comment('ไฟล์เอกสารแนบ');
            $table->timestamp('created_at')->nullable()->useCurrent()->comment('วันที่สร้างข้อมูล');
            $table->timestamp('updated_at')->nullable()->useCurrent()->comment('วันที่อัปเดตข้อมูล');
            $table->timestamp('deleted_at')->nullable()->comment('Soft delete');

            $table->foreign('cooperation_school_id')
                ->references('school_id')
                ->on('school_data')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};
