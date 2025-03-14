<?php

namespace Modules\Sandbox\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Sandbox\Models as Models;
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
        $school_data = Models\SchoolModel::all();

        $onet_result = Models\OnetResultsModel::all();
        $nt_result = Models\NtResultsModel::all();
        $onet_national_avg = Models\OnetNationalAvgModel::all();
        $onet_province_avg = Models\OnetProvinceAvgModel::all();

        $student_sum_data = DB::table('student_number')->join('grade_levels', 'student_number.grade_id', '=', 'grade_levels.id')->select('student_number.grade_id', 'grade_levels.grade_name', DB::raw('SUM(student_number.male_count) as total_male_count'), DB::raw('SUM(student_number.female_count) as total_female_count'))->groupBy('student_number.grade_id', 'grade_levels.grade_name')->get();

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
        $innovationData = $innovationController->getInnovationData(3);

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
}
