<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Modules\Dashboard\Policies\BasicSubjectAssessmentPolicy;
use Modules\Dashboard\Policies\CoreCompetencyAssessmentPolicy;
use Modules\Dashboard\Policies\InnovationPolicy;
use Modules\Dashboard\Policies\InnovationTypePolicy;
use Modules\Dashboard\Policies\SchoolPolicy;
use Modules\Dashboard\Policies\StudentNumberPolicy;
use Modules\Dashboard\Policies\UserPolicy;
use Modules\Sandbox\Models\BasicSubjectAssessmentModel;
use Modules\Sandbox\Models\CoreCompetencyAssessmentModel;
use Modules\Sandbox\Models\InnovationsModel;
use Modules\Sandbox\Models\InnovationTypesModel;
use Modules\Sandbox\Models\SchoolModel;
use Modules\Sandbox\Models\StudentNumberModel;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') !== 'local') {
            URL::forceScheme('https');
        }

        Gate::policy(SchoolModel::class, SchoolPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(InnovationsModel::class, InnovationPolicy::class);
        Gate::policy(InnovationTypesModel::class, InnovationTypePolicy::class);
        Gate::policy(StudentNumberModel::class, StudentNumberPolicy::class);
        Gate::policy(BasicSubjectAssessmentModel::class, BasicSubjectAssessmentPolicy::class);
        Gate::policy(CoreCompetencyAssessmentModel::class, CoreCompetencyAssessmentPolicy::class);

        Filament::serving(function () {
            $this->registerNavigationItems();
        });
    }

    protected function registerNavigationItems(): void
    {
        // เช็คว่าเป็น School Admin หรือไม่
        if (Auth::check() && Auth::user()->role === UserRole::SCHOOLADMIN) {
            $schoolId = Auth::user()->school_id;
            $currentYear = date('Y') + 543; // ปีปัจจุบันในรูปแบบ พ.ศ.

            // ตรวจสอบข้อมูล Basic Subject Assessment
            $hasBasicSubjectData = BasicSubjectAssessmentModel::where('school_id', $schoolId)
                ->where('education_year', $currentYear)
                ->exists();

            // ตรวจสอบข้อมูล Core Competency Assessment
            $hasCoreCompetencyData = CoreCompetencyAssessmentModel::where('school_id', $schoolId)
                ->where('education_year', $currentYear)
                ->exists();

            // แสดงการแจ้งเตือนสำหรับ Basic Subject Assessment
            if (!$hasBasicSubjectData) {
                Filament::registerNavigationItems([
                    NavigationItem::make('basic-subject-assessments')
                        ->label('ผลการประเมินสมรรถนะวิชาพื้นฐาน')
                        ->icon('heroicon-s-book-open')
                        ->group('การประเมินผล')
                        ->badge('New', 'danger')
                        ->sort(3)
                        ->url(route('filament.admin.resources.basic-subject-assessments.index'))
                        ->isActiveWhen(fn(): bool => request()->routeIs('filament.admin.resources.basic-subject-assessments.*')),
                ]);
            }

            // แสดงการแจ้งเตือนสำหรับ Core Competency Assessment
            if (!$hasCoreCompetencyData) {
                Filament::registerNavigationItems([
                    NavigationItem::make('core-competency-assessments')
                        ->label('ผลการประเมินสมรรถนะหลัก')
                        ->icon('heroicon-s-chart-bar')
                        ->group('การประเมินผล')
                        ->badge('New', 'danger')
                        ->sort(4)
                        ->url(route('filament.admin.resources.core-competency-assessments.index'))
                        ->isActiveWhen(fn(): bool => request()->routeIs('filament.admin.resources.core-competency-assessments.*')),
                ]);
            }
        }

        Filament::registerRenderHook(
            'panels::page.start',
            function (): ?string {
                // เช็คว่าอยู่ในหน้า Dashboard หรือไม่
                if (!request()->routeIs('filament.admin.pages.dashboard')) {
                    return null;
                }

                // เช็คว่าเป็น School Admin หรือไม่
                if (!Auth::check() || Auth::user()->role !== UserRole::SCHOOLADMIN) {
                    return null;
                }

                // เช็คสถานะข้อมูล
                $schoolId = Auth::user()->school_id;
                $currentYear = date('Y') + 543;
                $missingReports = [];
                $schoolName = SchoolModel::where('school_id', $schoolId)->value('school_name_th') ?? 'โรงเรียนของคุณ';

                if (!BasicSubjectAssessmentModel::where('school_id', $schoolId)->where('education_year', $currentYear)->exists()) {
                    $missingReports[] = [
                        'name' => 'ผลการประเมินวิชาพื้นฐาน',
                        'url' => route('filament.admin.resources.basic-subject-assessments.create'),
                        'icon' => 'heroicon-o-book-open',
                        'description' => 'บันทึกคะแนนวิชาภาษาไทย คณิตศาสตร์ วิทยาศาสตร์ และภาษาอังกฤษ'
                    ];
                }

                if (!CoreCompetencyAssessmentModel::where('school_id', $schoolId)->where('education_year', $currentYear)->exists()) {
                    $missingReports[] = [
                        'name' => 'ผลการประเมินสมรรถนะหลัก',
                        'url' => route('filament.admin.resources.core-competency-assessments.create'),
                        'icon' => 'heroicon-o-chart-bar',
                        'description' => 'บันทึกผลการประเมินสมรรถนะหลัก 6 ด้านตามหลักสูตร'
                    ];
                }

                if (empty($missingReports)) {
                    return null;
                }

                $html = <<<HTML
                <div class="mb-8 overflow-hidden rounded-xl bg-gradient-to-r from-primary-500 to-primary-600 shadow-lg animate__animated animate__fadeIn">
                    <div class="px-6 py-4">
                        <div class="flex items-center">
                            <div class="mr-5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold dark:text-white text-primary-100">การแจ้งเตือนสำหรับ{$schoolName}</h2>
                                <p class="text-primary-100">โรงเรียนของคุณยังไม่ได้บันทึกข้อมูลการประเมินปีการศึกษา {$currentYear}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-800">
                        <div class="grid grid-cols-1 md:grid-cols-{$this->getGridColumns(count($missingReports))} gap-4 p-6">
HTML;

                foreach ($missingReports as $report) {
                    $html .= <<<HTML
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-6 border border-gray-200 dark:border-gray-600 transition-all hover:shadow-md hover:scale-[1.01]">
                                <div class="flex flex-col h-full">
                                    <div class="flex items-center mb-4">
                                        <div class="p-2 rounded-lg bg-primary-100 dark:bg-primary-900">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-primary-500 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{$this->getSvgPath($report['icon'])}" />
                                            </svg>
                                        </div>
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-white ml-3">{$report['name']}</h3>
                                    </div>
                                    <p class="text-gray-600 dark:text-white mb-4 flex-grow">{$report['description']}</p>
                                    <div class="mt-2">
                                        <a href="{$report['url']}" class="w-full inline-flex items-center justify-center px-4 py-2 bg-primary-600 border border-transparent rounded-lg font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors">
                                            <span>กรอกข้อมูลสมรรถนะ</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </div>
HTML;
                }

                $html .= <<<HTML
                        </div>
                        <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700 text-right border-t border-gray-200 dark:border-gray-600">
                            <p class="text-xs text-gray-500 dark:text-gray-400">ข้อมูลที่บันทึกจะถูกนำไปใช้ในการวิเคราะห์และประเมินภาพรวมของสถานศึกษา</p>
                        </div>
                    </div>
                </div>
                
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
HTML;

                return $html;
            }
        );
    }

    // เพิ่มเมทอดเพื่อกำหนดจำนวนคอลัมน์ตามจำนวนรายการ
    private function getGridColumns(int $count): int
    {
        return min($count, 2); // สูงสุด 2 คอลัมน์
    }

    // เพิ่มเมทอดสำหรับกำหนด path ของ SVG icon
    private function getSvgPath(string $icon): string
    {
        return match ($icon) {
            'heroicon-o-book-open' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
            'heroicon-o-chart-bar' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
            default => 'M12 6v6m0 0v6m0-6h6m-6 0H6'
        };
    }
}
