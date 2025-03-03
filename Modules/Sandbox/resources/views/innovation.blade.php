@include('sandbox::styles.inno-style')

<div class="innovation">
    <hr class="rounded">
    <h2 class="mt-4 mb-4 text-start">นวัตกรรมทั้งหมด</h2>
    <div>
        <div class="row">
            @foreach ($innovationData as $innovation)
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm hover:shadow-lg transition-shadow duration-300"
                        style="border-radius: 12px; border: none;">
                        <div class="card-body p-4 d-flex flex-column">
                            <!-- หัวข้อการ์ด -->
                            <div class="card-title">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-school fa-sm me-2" style="color: #4a90e2"></i>
                                        <h6 class="mb-0 text-muted">โรงเรียน{{ $innovation->school->school_name_th }}
                                        </h6>
                                    </div>
                                    <small class="text-muted" style="white-space: nowrap;">
                                        <i class="far fa-calendar-alt me-1"></i>
                                        @php
                                            $date = new DateTime($innovation->created_at);
                                            $date->modify('+543 years');
                                            echo $date->format('d/m/Y');
                                        @endphp
                                    </small>
                                </div>
                                <h4 class="fw-bold mb-2 text-card-title-3" style="color: #2c3e50">
                                    {{ $innovation->inno_name }}</h4>
                                <h6 class="text-success">{{ $innovation->innovationType->name }}</h6>
                            </div>

                            <hr class="mb-3" style="background-color: #edf2f7">

                            <!-- เนื้อหา -->
                            <p class="card-text mb-4 text-card-body-3" style="color: #4a5568;">
                                {{ $innovation->inno_description }}
                            </p>

                            <div class="mt-auto">
                                <!-- ไฟล์แนบและวิดีโอ -->
                                @if ($innovation->attachments || $innovation->video_url)
                                    <div class="mb-4">
                                        <div class="d-flex flex-column gap-2">
                                            @if ($innovation->attachments)
                                                <a href="{{ $innovation->attachments }}" target="_blank"
                                                    class="d-flex align-items-center p-2 text-decoration-none rounded hover:bg-gray-100"
                                                    style="background-color: #f8fafc;">
                                                    <i class="far fa-file-pdf me-2" style="color: #dc3545"></i>
                                                    <span class="text-muted">เอกสารแนบ</span>
                                                    <i class="fas fa-download ms-auto" style="color: #4a90e2"></i>
                                                </a>
                                            @endif

                                            @if ($innovation->video_url)
                                                @php
                                                    // ตรวจสอบว่าเป็นลิงก์ของ Facebook หรือไม่
                                                    $isFacebook =
                                                        strpos($innovation->video_url, 'facebook.com') !== false;

                                                    // ตั้งค่าชื่อวิดีโอ
                                                    $videoTitle = $isFacebook
                                                        ? 'คลิกเพื่อดูวิดีโอบน Facebook'
                                                        : 'คลิกเพื่อดูวิดีโอบน Youtube';

                                                    // เลือกไอคอนให้เหมาะสม
                                                    $iconClass = $isFacebook ? 'fab fa-facebook' : 'fab fa-youtube';
                                                    $iconColor = $isFacebook ? '#1877f2' : '#dc3545';
                                                @endphp

                                                <!-- ปุ่มแสดงลิงก์วิดีโอ -->
                                                <a href="{{ $innovation->video_url }}" target="_blank"
                                                    class="d-flex align-items-center p-2 text-decoration-none rounded hover:bg-gray-100"
                                                    style="background-color: #f8fafc;">
                                                    <i class="{{ $iconClass }} me-2"
                                                        style="color: {{ $iconColor }}"></i>
                                                    <span class="text-muted">{{ $videoTitle }}</span>
                                                    <i class="fas fa-external-link-alt ms-auto"
                                                        style="color: #4a90e2"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                <!-- แท็ก -->
                                <div class="row g-2 mb-4">
                                    @php
                                        $tags = is_string($innovation->tags)
                                            ? json_decode($innovation->tags, true)
                                            : $innovation->tags;
                                    @endphp
                                    @if (is_array($tags))
                                        @foreach ($tags as $tag)
                                            <div class="col-auto">
                                                <div class="card bg-primary bg-opacity-10 border-0">
                                                    <div class="card-body py-1 px-3">
                                                        <small
                                                            class="text-primary fw-medium">{{ $tag }}</small>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>

                                <hr class="my-3" style="background-color: #edf2f7">

                                <a href="#"
                                    class="btn btn-primary w-100 rounded-pill hover:bg-primary-dark transition-colors duration-300"
                                    style="background-color: #4a90e2; border: none;">
                                    รายละเอียดเพิ่มเติม
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
