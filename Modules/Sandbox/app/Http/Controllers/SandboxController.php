<?php

namespace Modules\Sandbox\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Sandbox\Models\InnovationsModel;
use Modules\Sandbox\Models\NtResultsModel;
use Modules\Sandbox\Models\OnetNationalAvgModel;
use Modules\Sandbox\Models\OnetProvinceAvgModel;
use Modules\Sandbox\Models\OnetResultsModel;
use Modules\Sandbox\Models\SchoolModel;
use Modules\Sandbox\Services\ResultProcessingService;

class SandboxController extends Controller
{
    protected $resultProcessingService;

    public function __construct(ResultProcessingService $resultProcessingService)
    {
        $this->resultProcessingService = $resultProcessingService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $school_data = SchoolModel::all();

        $onet_result = OnetResultsModel::all();
        $nt_result = NtResultsModel::all();
        $onet_national_avg = OnetNationalAvgModel::all();
        $onet_province_avg = OnetProvinceAvgModel::all();

        $student_sum_data = DB::table('student_number')->join('grade_levels', 'student_number.year_id', '=', 'grade_levels.id')->select('student_number.year_id', 'grade_levels.grade_name', DB::raw('SUM(student_number.male_count) as total_male_count'), DB::raw('SUM(student_number.female_count) as total_female_count'))->groupBy('student_number.year_id', 'grade_levels.grade_name')->get();

        $locations = DB::table('school_data')->select('school_name_th', 'latitude', 'longitude')->get();

        $onet_subjects = ['math', 'thai', 'english', 'science', 'social'];
        $nt_subjects = ['math', 'thai'];

        // Initialize arrays
        $nt_totals = $nt_counts = $nt_averages = [];
        $onet_totals = $onet_counts = $onet_averages = [];

        // Process ONET and NT results
        $this->resultProcessingService->processResults($onet_result, $onet_subjects, $onet_totals, $onet_counts);
        $this->resultProcessingService->processResults($nt_result, $nt_subjects, $nt_totals, $nt_counts);

        // Calculate averages for ONET and NT
        $this->resultProcessingService->calculateAverages($onet_subjects, $onet_totals, $onet_counts, $onet_averages);
        $this->resultProcessingService->calculateAverages($nt_subjects, $nt_totals, $nt_counts, $nt_averages);

        $nt_result = [];
        foreach ($nt_averages as $subject => $grades) {
            foreach ($grades as $grade => $average) {
                $nt_result[$subject] = $average;
            }
        }

         // 🔹 เรียกใช้งาน InnovationController
         $innovationController = new InnovationController();
         $innovationData = $innovationController->getInnovationData();
 
         return view('sandbox::index', compact(
             'school_data',
             'student_sum_data',
             'locations',
             'onet_result',
             'onet_national_avg',
             'onet_province_avg',
             'onet_averages',
             'nt_result',
             'innovationData'
         ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('sandbox::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('sandbox::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('sandbox::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
