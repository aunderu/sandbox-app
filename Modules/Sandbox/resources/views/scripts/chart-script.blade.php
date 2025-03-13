{{-- Charts --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

{{-- Google Map --}}
{{-- !! Need change --}}
<script
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCtPm3bDmAiJFncYg1vEaPqGwMBgZP0nrI&loading=async&callback=initMap&v=weekly"
    async defer></script>

<script>
    var lineChartCtx = document.getElementById('schoolSum').getContext('2d');
    var studentChartCtx = document.getElementById('studentChart').getContext('2d');
    var radarChart1Ctx = document.getElementById('radarChart1').getContext('2d');
    var radarChart2Ctx = document.getElementById('radarChart2').getContext('2d');
    var stackedBarLineCtx = document.getElementById('stackedBarLine').getContext('2d');
    var barChartCtx = document.getElementById('barChart').getContext('2d');

    const testRadar1Data = {
        labels: [
            'ภาษาไทย',
            'ภาษาอังกฤษ',
            'คณิตศาสาตร์',
            'วิทยาศาสตร์',
        ],
        datasets: [{
            label: 'ระดับจังหวัด',
            data: [84, 90, 70, 87],
            fill: true,
            backgroundColor: 'rgba(255, 99, 132, 0.2)',
            borderColor: 'rgb(255, 99, 132)',
            pointBackgroundColor: 'rgb(255, 99, 132)',
            pointBorderColor: '#fff',
            pointHoverBackgroundColor: '#fff',
            pointHoverBorderColor: 'rgb(255, 99, 132)'
        }, {
            label: 'ระดับประเทศ',
            data: [81, 92, 62, 64],
            fill: true,
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgb(54, 162, 235)',
            pointBackgroundColor: 'rgb(54, 162, 235)',
            pointBorderColor: '#fff',
            pointHoverBackgroundColor: '#fff',
            pointHoverBorderColor: 'rgb(54, 162, 235)'
        }]
    };

    const testRadar2Data = {
        labels: [
            'การจัดการตนเอง',
            'การคิดขั้นสูง',
            'การสื่อสาร',
            'พลเมืองที่เข้มแข็ง',
            'การอยู่ร่วมกับธรรมชาติ',
            'การทำงานเป็นทีม',
        ],
        datasets: [{
            label: 'ระดับจังหวัด',
            data: [7, 9, 8, 9, 5, 6],
            fill: true,
            backgroundColor: 'rgba(255, 99, 132, 0.2)',
            borderColor: 'rgb(255, 99, 132)',
            pointBackgroundColor: 'rgb(255, 99, 132)',
            pointBorderColor: '#fff',
            pointHoverBackgroundColor: '#fff',
            pointHoverBorderColor: 'rgb(255, 99, 132)'
        }, {
            label: 'ระดับประเทศ',
            data: [8, 9, 7, 6, 5, 6],
            fill: true,
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgb(54, 162, 235)',
            pointBackgroundColor: 'rgb(54, 162, 235)',
            pointBorderColor: '#fff',
            pointHoverBackgroundColor: '#fff',
            pointHoverBorderColor: 'rgb(54, 162, 235)'
        }]
    };


    var schoolSum = new Chart(lineChartCtx, {
        type: 'line',
        data: {
            labels: @json($school_data->pluck('school_name_th')),
            datasets: [{
                label: 'จำนวนนักเรียน',
                data: @json($school_data->pluck('student_amount')),
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1,
                fill: false
            }, {
                label: 'จำนวนนักเรียนด้อยโอกาส',
                // data: disadventage_student_amount,
                data: @json($school_data->pluck('disadvantaged_student_amount')),
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 1,
                fill: false
            }, {
                label: 'จำนวนครู',
                data: @json($school_data->pluck('teacher_amount')),
                backgroundColor: 'rgba(255, 205, 86, 0.2)',
                borderColor: 'rgba(255, 205, 86, 1)',
                borderWidth: 1,
                fill: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'จำนวนนักเรียนและบุคลากรทั้งหมดของแต่ละโรงเรียน',
                    font: {
                        size: 24
                    }
                }
            },
            scales: {
                x: {
                    display: true,
                    title: {
                        display: true,
                        text: 'โรงเรียน'
                    }
                },
                y: {
                    display: true,
                    title: {
                        display: true,
                        text: 'จำนวน'
                    }
                }
            }
        }
    });

    var studentChart = new Chart(studentChartCtx, {
        type: 'bar',
        data: {
            labels: @json($student_sum_data->pluck('grade_name')),
            datasets: [{
                    label: 'นักเรียนชาย',
                    data: @json($student_sum_data->pluck('total_male_count')),
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                },
                {
                    label: 'นักเรียนหญิง',
                    data: @json($student_sum_data->pluck('total_female_count')),
                    backgroundColor: 'rgba(255, 99, 132, 0.5)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'จำนวนนักเรียนทั้งหมดของแต่ละชั้นปี',
                    font: {
                        size: 24
                    }
                }
            },
            scales: {
                x: {
                    stacked: true,
                },
                y: {
                    beginAtZero: true,
                    stacked: true
                }
            }
        }
    });

    var radarChart1 = new Chart(radarChart1Ctx, {
        type: 'radar',
        data: testRadar1Data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'สมรรถนะวิชาพื้นฐาน',
                    font: {
                        size: 24
                    }
                },
                legend: {
                    position: 'bottom' // ย้ายป้ายกำกับลงไปด้านล่าง
                }
            },
            elements: {
                line: {
                    borderWidth: 3,
                }
            }
        }
    })

    var radarChart2 = new Chart(radarChart2Ctx, {
        type: 'radar',
        data: testRadar2Data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'สมรรถนะหลัก',
                    font: {
                        size: 24
                    }
                },
                legend: {
                    position: 'bottom' // ย้ายป้ายกำกับลงไปด้านล่าง
                }
            },
            elements: {
                line: {
                    borderWidth: 3,
                }
            }
        }
    })

    var stackedBarLine = new Chart(stackedBarLineCtx, {
        type: 'bar',
        data: {
            labels: ["ประถมศึกษาปีที่ 6", "มัธยมศึกษาปีที่ 3", "มัธยมศึกษาปีที่ 6"],
            datasets: [{
                    label: 'คณิตศาสตร์',
                    data: @json(array_values($onet_averages['math'])),
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                },
                {
                    label: "ภาษาไทย",
                    data: @json(array_values($onet_averages['thai'])),
                    backgroundColor: "rgba(255, 99, 132, 0.5)",
                    borderColor: "rgba(255, 99, 132, 1)",
                    borderWidth: 1,
                },
                {
                    label: "ภาษาอังกฤษ",
                    data: @json(array_values($onet_averages['english'])),
                    backgroundColor: "rgba(255, 205, 86, 0.5)",
                    borderColor: "rgba(255, 205, 86, 1)",
                    borderWidth: 1,
                },
                {
                    label: "สังคมศึกษา",
                    data: @json(array_values($onet_averages['social'])),
                    backgroundColor: "rgba(3, 255, 3, 0.5)",
                    borderColor: "rgba(3, 255, 3, 1)",
                    borderWidth: 1,
                },
                {
                    label: "ค่าเฉลี่ยระดับประเทศ",
                    data: @json($onet_national_avg->pluck('total_avg')),
                    backgroundColor: "rgba(255, 3, 3)",
                    borderColor: "rgba(255, 3, 3)",
                    borderWidth: 2,
                    pointStyle: 'circle',
                    pointRadius: 5,
                    type: 'line',
                    fill: false,
                },
                {
                    label: "ค่าเฉลี่ยระดับจังหวัด",
                    data: @json($onet_province_avg->pluck('total_avg')),
                    backgroundColor: "rgba(54, 162, 235, 1)",
                    borderColor: "rgba(54, 162, 235, 1)",
                    borderWidth: 2,
                    pointStyle: 'circle',
                    pointRadius: 5,
                    type: 'line',
                    fill: false,
                },
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'คะแนนเฉลี่ย O-NET แยกตามระดับชั้น ปีการศึกษา 2566',
                    font: {
                        size: 24
                    }
                },
                legend: {
                    position: 'bottom' // ย้ายป้ายกำกับลงไปด้านล่าง
                }
            },
            elements: {
                line: {
                    borderWidth: 3,
                }
            }
        }
    });

    var barChart = new Chart(barChartCtx, {
        type: 'bar',
        data: {
            labels: ["คณิตศาสตร์", "ภาษาไทย"],
            datasets: [{
                    label: 'คะแนนเฉลี่ย',
                    data: @json(array_values($nt_result)),
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                },
                {
                    label: "ค่าเฉลี่ยระดับจังหวัด",
                    data: @json($onet_province_avg->pluck('total_avg')),
                    backgroundColor: "rgba(54, 162, 235, 1)",
                    borderColor: "rgba(54, 162, 235, 1)",
                    borderWidth: 2,
                },
                {
                    label: "ค่าเฉลี่ยระดับประเทศ",
                    data: @json($onet_national_avg->pluck('total_avg')),
                    backgroundColor: "rgba(255, 3, 3)",
                    borderColor: "rgba(255, 3, 3)",
                    borderWidth: 2,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'คะแนนเฉลี่ย NT แยกตามระดับชั้น ปีการศึกษา 2566',
                    font: {
                        size: 24
                    }
                },
                legend: {
                    position: 'bottom' // ย้ายป้ายกำกับลงไปด้านล่าง
                }
            },
            elements: {
                line: {
                    borderWidth: 3,
                }
            }
        }
    });


    // function filterChart

    function filterChart() {
        // const filterResult = schoolSum.data.labels.filter(value => value = "นิบงชนูปถัมภ์");
        // const filterData = schoolSum.data.datasets.data.filter(value => value = "นิบงชนูปถัมภ์");
        const labelResult = schoolSum.data.labels.filter(value => value == "นิบงชนูปถัมภ์");
        console.log(labelResult);
    }

    // map
    function initMap() {
        var locations = @json($locations);

        var map = new google.maps.Map(document.getElementById('googleMap'), {
            zoom: 9,
            center: {
                lat: 6.5428, // Latitude for Yala, Thailand
                lng: 101.2800 // Longitude for Yala, Thailand
            },
        });

        locations.forEach(function(location) {
            var marker = new google.maps.Marker({
                position: {
                    lat: parseFloat(location.latitude),
                    lng: parseFloat(location.longitude)
                },
                map: map
            });

            var infowindow = new google.maps.InfoWindow({
                content: `โรงเรียน${location.school_name_th}`
            });

            marker.addListener('click', function() {
                infowindow.open(map, marker);
            });
        });
    }
</script>
