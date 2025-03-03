<div>
    <div class="inputBox mb-3">
        <div class="row">
            <div class="col-md-5">
                <div class="form-group">
                    <input id="schoolDropdown" name="schoolDropdown" type="text" list="custom_field1_datalist"
                        class="form-control" placeholder="ค้นหาโรงเรียน">
                    <datalist id="custom_field1_datalist">
                        @foreach ($schools as $school)
                            <option value="{{ $school->school_name_th }}">โรงเรียน{{ $school->school_name_th }}</option>
                        @endforeach
                    </datalist>
                    <span id="error" class="text-danger"></span>
                </div>
            </div>
            <div class="col-md-3">
                <button type="button" class="btn btn-outline-success" value="Filter" onclick="filterChart()"><i
                        class="fa-solid fa-magnifying-glass"></i>
                    กรองข้อมูล</button>
            </div>
        </div>
    </div>
</div>
