<x-filament-widgets::widget>
    <x-filament::section class="h-full">
        <div class="flex justify-center">
            <div class="w-full max-w-2xl">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        กราฟแสดงสมรรถนะหลัก
                    </h2>
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        ปีการศึกษา {{ $record->education_year }}
                    </span>
                </div>

                <div class="mt-4">
                    <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div style="height: 300px;">
                            <canvas id="competency-radar-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const ctx = document.getElementById('competency-radar-chart');

                const selfScore = {{ $record->self_management_score ?? 0 }};
                const teamworkScore = {{ $record->teamwork_score ?? 0 }};
                const highThinkingScore = {{ $record->high_thinking_score ?? 0 }};
                const communicationScore = {{ $record->communication_score ?? 0 }};
                const activeCitizenScore = {{ $record->active_citizen_score ?? 0 }};
                const sustainableCoexistenceScore = {{ $record->sustainable_coexistence_score ?? 0 }};

                const avgScore = ((selfScore + teamworkScore + highThinkingScore + communicationScore +
                    activeCitizenScore + sustainableCoexistenceScore) / 6).toFixed(2);

                // ตรวจสอบโหมดธีม (มืด/สว่าง)
                const isDarkMode = document.documentElement.classList.contains('dark');
                const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.2)' : 'rgba(0, 0, 0, 0.1)';
                const textColor = isDarkMode ? '#e5e7eb' : '#374151';

                new Chart(ctx, {
                    type: 'radar',
                    data: {
                        labels: [
                            'การจัดการตนเอง',
                            'การทำงานเป็นทีม',
                            'การคิดขั้นสูง',
                            'การสื่อสาร',
                            'การเป็นพลเมืองที่เข้มแข็ง',
                            'การอยู่ร่วมกับธรรมชาติอย่างยั่งยืน'
                        ],
                        datasets: [{
                            label: 'คะแนนสมรรถนะหลัก',
                            data: [
                                selfScore,
                                teamworkScore,
                                highThinkingScore,
                                communicationScore,
                                activeCitizenScore,
                                sustainableCoexistenceScore
                            ],
                            fill: true,
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            borderColor: 'rgb(75, 192, 192)',
                            pointBackgroundColor: 'rgb(75, 192, 192)',
                            pointBorderColor: '#fff',
                            pointHoverBackgroundColor: '#fff',
                            pointHoverBorderColor: 'rgb(75, 192, 192)'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        elements: {
                            line: {
                                borderWidth: 3
                            }
                        },
                        scales: {
                            r: {
                                angleLines: {
                                    display: true,
                                    color: gridColor,
                                    lineWidth: 1.5
                                },
                                grid: {
                                    color: gridColor,
                                    lineWidth: 1.5
                                },
                                pointLabels: {
                                    color: textColor,
                                    font: {
                                        size: 12,
                                        weight: 'bold'
                                    }
                                },
                                suggestedMin: 0,
                                suggestedMax: 100,
                                ticks: {
                                    stepSize: 20,
                                    color: textColor,
                                    backdropColor: 'transparent'
                                }
                            }
                        },
                        plugins: {
                            title: {
                                display: true,
                                text: 'คะแนนเฉลี่ย: ' + avgScore + '/100',
                                padding: {
                                    top: 10,
                                    bottom: 10
                                },
                                color: textColor, // ปรับสีข้อความ
                                font: {
                                    size: 16,
                                    weight: 'bold'
                                }
                            },
                            legend: {
                                display: true,
                                position: 'top',
                                labels: {
                                    color: textColor, // ปรับสีข้อความ
                                    font: {
                                        size: 12
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.parsed.r + ' คะแนน';
                                    }
                                }
                            }
                        },
                        animation: {
                            duration: 1000
                        }
                    }
                });

                // ตรวจจับการเปลี่ยนโหมดธีม (มืด/สว่าง) และอัพเดตกราฟถ้ามีการเปลี่ยน
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.attributeName === 'class' &&
                            mutation.target === document.documentElement &&
                            mutation.target.classList.contains('dark') !== isDarkMode) {
                            // โหมดธีมเปลี่ยน ให้โหลดหน้าใหม่เพื่ออัพเดตกราฟ
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
