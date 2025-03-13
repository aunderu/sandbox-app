{{-- style --}}
@include('sandbox::styles.sum-style')

{{-- content --}}
<div class="school-number">
    <div class="row">
        <div class="col mb-3">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-body d-flex align-items-center justify-content-center">
                    <div class="col-3 text-center">
                        <div class="icon-circle school shadow-sm">
                            <i class="fas fa-school fa-2x" style="color: #ffffff"></i>
                        </div>
                    </div>
                    <div class="col-9 text-center">
                        <h5 class="card-title">สถานศึกษา</h5>

                        <h1 class="card-text" style="font-weight: bold">{{ number_format($school_data->count()) }} <span
                                class="custom-text">แห่ง</span>
                        </h1>
                    </div>
                </div>
            </div>
        </div>
        <div class="col mb-3">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-body d-flex align-items-center justify-content-center">
                    <div class="col-3 text-center">
                        <div class="icon-circle student">
                            <i class="fas fa-user-graduate fa-2x" style="color: #ffffff"></i>
                        </div>
                    </div>
                    <div class="col-9 text-center">
                        <h5 class="card-title">นักเรียนทั้งหมด</h5>
                        <h1 class="card-text" style="font-weight: bold">
                            {{ number_format($school_data->sum('student_amount') + $school_data->sum('disadvantaged_student_amount')) }}
                            <span class="custom-text">คน</span>
                        </h1>
                    </div>
                </div>
            </div>
        </div>
        <div class="col mb-3">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-body d-flex align-items-center justify-content-center">
                    <div class="col-3 text-center">
                        <div class="icon-circle teacher">
                            <i class="fas fa-chalkboard-teacher fa-2x" style="color: #ffffff"></i>
                        </div>
                    </div>
                    <div class="col-9 text-center">
                        <h5 class="card-title">ครูทั้งหมด</h5>
                        <h1 class="card-text" style="font-weight: bold">
                            {{ number_format($school_data->sum('teacher_amount')) }} <span class="custom-text">คน</span>
                        </h1>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
