@include('sandbox::styles.inno-style')

<div class="innovation">
    <hr class="rounded">
    <h2 class="mt-4 mb-4 text-start">นวัตกรรมทั้งหมด</h2>
    <div>
        <div class="row" id="innovation-container">
            @include('sandbox::partials.innovation-items', ['innovations' => $innovationData])
        </div>
        <div id="loading" class="text-center my-3" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">กำลังโหลด...</span>
            </div>
            <div>กำลังโหลด...</div>
        </div>
        <div id="end-of-content" class="text-center my-3" style="display: none;">
            {{-- <img src="https://via.placeholder.com/150" alt="No more data" class="mb-2"> --}}
            <div>ไม่มีข้อมูลเพิ่มเติม</div>
        </div>
        <div id="error-message" class="text-center my-3 text-danger" style="display: none;">
            เกิดข้อผิดพลาดในการโหลดข้อมูล กรุณาลองใหม่อีกครั้ง
        </div>
    </div>
</div>