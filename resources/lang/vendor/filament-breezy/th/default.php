<?php

return [
    'password_confirm' => [
        'heading' => 'ยืนยันรหัสผ่าน',
        'description' => 'กรุณายืนยันรหัสผ่านของคุณเพื่อดำเนินการนี้',
        'current_password' => 'รหัสผ่านปัจจุบัน',
    ],
    'two_factor' => [
        'heading' => 'การท้าทายสองขั้นตอน',
        'description' => 'กรุณายืนยันการเข้าถึงบัญชีของคุณโดยการป้อนรหัสที่ให้โดยแอปพลิเคชันการตรวจสอบของคุณ',
        'code_placeholder' => 'XXX-XXX',
        'recovery' => [
            'heading' => 'การท้าทายสองขั้นตอน',
            'description' => 'กรุณายืนยันการเข้าถึงบัญชีของคุณโดยการป้อนหนึ่งในรหัสฉุกเฉินของคุณ',
        ],
        'recovery_code_placeholder' => 'abcdef-98765',
        'recovery_code_text' => 'อุปกรณ์สูญหาย?',
        'recovery_code_link' => 'ใช้รหัสฉุกเฉิน',
        'back_to_login_link' => 'กลับไปที่หน้าเข้าสู่ระบบ',
    ],
    'profile' => [
        'account' => 'บัญชี',
        'profile' => 'โปรไฟล์',
        'my_profile' => 'โปรไฟล์ของฉัน',
        'subheading' => 'จัดการโปรไฟล์ผู้ใช้ของคุณที่นี่',
        'personal_info' => [
            'heading' => 'ข้อมูลส่วนตัว',
            'subheading' => 'จัดการข้อมูลส่วนตัวของคุณ',
            'submit' => [
                'label' => 'อัปเดต',
            ],
            'notify' => 'อัปเดตโปรไฟล์สำเร็จ!',
        ],
        'password' => [
            'heading' => 'รหัสผ่าน',
            'subheading' => 'แนะนำให้รหัสมีตัวอักษรอย่างน้อย 8 ตัวอักษรเพื่อความปลอดภัยของบัญชีของคุณ',
            'submit' => [
                'label' => 'อัปเดต',
            ],
            'notify' => 'อัปเดตรหัสผ่านสำเร็จ!',
        ],
        '2fa' => [
            'title' => 'Two Factor Authentication',
            'description' => 'จัดการการตรวจสอบสองขั้นตอนสำหรับบัญชีของคุณ (แนะนำ)',
            'actions' => [
                'enable' => 'เปิดใช้งาน',
                'regenerate_codes' => 'สร้างรหัสฉุกเฉินใหม่',
                'disable' => 'ปิดใช้งาน',
                'confirm_finish' => 'ยืนยันและเสร็จสิ้น',
                'cancel_setup' => 'ยกเลิกการตั้งค่า',
            ],
            'setup_key' => 'คีย์การตั้งค่า',
            'must_enable' => 'คุณต้องเปิดใช้งานการตรวจสอบสองขั้นตอนเพื่อใช้แอปพลิเคชันนี้',
            'not_enabled' => [
                'title' => 'คุณยังไม่ได้เปิดใช้งานการตรวจสอบสองขั้นตอน',
                'description' => 'เมื่อเปิดใช้งานการตรวจสอบสองขั้นตอนแล้ว คุณจะถูกขอรหัสโทเค็นที่ปลอดภัยและสุ่มขณะการตรวจสอบสิทธิ คุณสามารถใช้แอปพลิเคชันตรวจสอบสองขั้นตอนบนโทรศัพท์สมาร์ทของคุณ เช่น Google Authenticator, Microsoft Authenticator เป็นต้น เพื่อให้ง่ายขึ้น',
            ],
            'finish_enabling' => [
                'title' => 'เสร็จสิ้นการเปิดใช้งานการตรวจสอบสองขั้นตอน',
                'description' => "เพื่อเสร็จสิ้นการเปิดใช้งานการตรวจสอบสองขั้นตอน สแกนรหัส QR ต่อไปนี้โดยใช้แอปพลิเคชันตรวจสอบสองขั้นตอนบนโทรศัพท์ของคุณ หรือป้อนคีย์การตั้งค่าและให้รหัส OTP ที่สร้างขึ้น",
            ],
            'enabled' => [
                'notify' => 'การตรวจสอบสองขั้นตอนเปิดใช้งานแล้ว',
                'title' => 'คุณได้เปิดใช้งานการตรวจสอบสองขั้นตอนแล้ว!',
                'description' => 'การตรวจสอบสองขั้นตอนได้เปิดใช้งานแล้ว สิ่งนี้ช่วยทำให้บัญชีของคุณปลอดภัยมากขึ้น',
                'store_codes' => 'รหัสเหล่านี้สามารถใช้เพื่อกู้คืนการเข้าถึงบัญชีของคุณหากอุปกรณ์ของคุณสูญหาย คำเตือน! รหัสเหล่านี้จะแสดงเพียงครั้งเดียวเท่านั้น',
            ],
            'disabling' => [
                'notify' => 'การตรวจสอบสองขั้นตอนได้ถูกปิดใช้งานแล้ว',
            ],
            'regenerate_codes' => [
                'notify' => 'สร้างรหัสฉุกเฉินใหม่เรียบร้อยแล้ว',
            ],
            'confirmation' => [
                'success_notification' => 'รหัสได้รับการยืนยัน การตรวจสอบสองขั้นตอนได้เปิดใช้งานแล้ว',
                'invalid_code' => 'รหัสที่คุณป้อนไม่ถูกต้อง',
            ],
        ],
        'sanctum' => [
            'title' => 'โทเค็น API',
            'description' => 'จัดการโทเค็น API ที่อนุญาตให้บริการภายนอกเข้าถึงแอปพลิเคชันนี้ในนามของคุณ',
            'create' => [
                'notify' => 'สร้างโทเค็นสำเร็จ!',
                'message' => 'โทเค็นของคุณจะแสดงเพียงครั้งเดียวเมื่อสร้าง หากคุณสูญหายโทเค็นของคุณ คุณจะต้องลบและสร้างใหม่',
                'submit' => [
                    'label' => 'สร้าง',
                ],
            ],
            'update' => [
                'notify' => 'อัปเดทโทเค็นสำเร็จ!',
            ],
            'copied' => [
                'label' => 'ฉันได้คัดลอกโทเค็นของฉันแล้ว',
            ],
        ],
    ],
    'clipboard' => [
        'link' => 'คัดลอกไปยังคลิปบอร์ด',
        'tooltip' => 'คัดลอกแล้ว!',
    ],
    'fields' => [
        'avatar' => 'รูปประจำตัว',
        'email' => 'อีเมล',
        'login' => 'เข้าสู่ระบบ',
        'name' => 'ชื่อ',
        'password' => 'รหัสผ่าน',
        'password_confirm' => 'ยืนยันรหัสผ่าน',
        'new_password' => 'รหัสผ่านใหม่',
        'new_password_confirmation' => 'ยืนยันรหัสผ่านใหม่',
        'token_name' => 'ชื่อโทเค็น',
        'token_expiry' => 'วันหมดอายุของโทเค็น',
        'abilities' => 'ความสามารถ',
        '2fa_code' => 'รหัส',
        '2fa_recovery_code' => 'รหัสกู้คืน',
        'created' => 'สร้างเมื่อ',
        'expires' => 'หมดอายุเมื่อ',
    ],
    'or' => 'หรือ',
    'cancel' => 'ยกเลิก',
];