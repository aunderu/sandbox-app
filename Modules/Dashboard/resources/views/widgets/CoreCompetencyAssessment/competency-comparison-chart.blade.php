<x-filament-widgets::widget>
    <x-filament::section class="h-full">
        <div class="h-full flex flex-col">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                เปรียบเทียบกับค่าเฉลี่ยของทุกโรงเรียน
            </h2>

            <div class="mt-4 h-64">
                <canvas id="competency-comparison-chart" style="max-height: 100%; width: 100%;"></canvas>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const averages = @json($this->getAverageScores());

                const ctx = document.getElementById('competency-comparison-chart');

                // ตรวจสอบโหมดธีม (มืด/สว่าง)
                const isDarkMode = document.documentElement.classList.contains('dark');
                const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
                const textColor = isDarkMode ? '#e5e7eb' : '#374151';

                const grayColor = isDarkMode ? 'rgba(156, 163, 175, 0.7)' : 'rgba(107, 114, 128, 0.7)';
                const grayBorderColor = isDarkMode ? 'rgb(156, 163, 175)' : 'rgb(107, 114, 128)';

                const chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['การจัดการตนเอง', 'การทำงานเป็นทีม', 'การคิดขั้นสูง', 'การสื่อสาร',
                            'พลเมืองเข้มแข็ง', 'อยู่กับธรรมชาติ'
                        ],
                        datasets: [{
                                label: 'คะแนนโรงเรียนนี้',
                                data: [
                                    {{ $record->self_management_score ?? 0 }},
                                    {{ $record->teamwork_score ?? 0 }},
                                    {{ $record->high_thinking_score ?? 0 }},
                                    {{ $record->communication_score ?? 0 }},
                                    {{ $record->active_citizen_score ?? 0 }},
                                    {{ $record->sustainable_coexistence_score ?? 0 }}
                                ],
                                backgroundColor: 'rgba(54, 162, 235, 0.7)',
                                borderColor: 'rgb(54, 162, 235)',
                                borderWidth: 1
                            },
                            {
                                label: 'ค่าเฉลี่ยทุกโรงเรียน',
                                data: [
                                    averages.self,
                                    averages.teamwork,
                                    averages.thinking,
                                    averages.communication,
                                    averages.citizen,
                                    averages.nature
                                ],
                                backgroundColor: grayColor,
                                borderColor: grayBorderColor,
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                grid: {
                                    color: gridColor,
                                },
                                ticks: {
                                    color: textColor,
                                }
                            },
                            x: {
                                grid: {
                                    color: gridColor,
                                },
                                ticks: {
                                    color: textColor,
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                align: 'center',
                                labels: {
                                    boxWidth: 15,
                                    padding: 15,
                                    color: textColor,
                                    font: {
                                        size: 12
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': ' + context.parsed.y + ' คะแนน';
                                    }
                                }
                            }
                        }
                    }
                });

                // ตรวจจับการเปลี่ยนธีม
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.attributeName === 'class' &&
                            mutation.target === document.documentElement &&
                            mutation.target.classList.contains('dark') !== isDarkMode) {
                            window.location.reload();
                        }
                    });
                });

                observer.observe(document.documentElement, {
                    attributes: true
                });
            });
        </script>
    </x-filament::section>
</x-filament-widgets::widget>
