<?php

namespace Modules\Sandbox\Services;

class ResultProcessingService
{
    public function processResults($results, $subjects, &$totals, &$counts)
    {
        foreach ($results as $result) {
            $grade = $result->grade_id;
            foreach ($subjects as $subject) {
                $field = "{$subject}_score";
                if ($result->$field !== null) {
                    if (!isset($totals[$subject][$grade])) {
                        $totals[$subject][$grade] = 0;
                        $counts[$subject][$grade] = 0;
                    }
                    $totals[$subject][$grade] += $result->$field;
                    $counts[$subject][$grade]++;
                }
            }
        }
    }

    public function calculateAverages($subjects, $totals, $counts, &$averages)
    {
        foreach ($subjects as $subject) {
            if ($subject == 'social') {
                foreach ([9, 12] as $grade) {
                    if (!isset($averages[$subject][$grade])) {
                        $averages[$subject][$grade] = 0;
                    }
                }
            }

            foreach ($totals[$subject] as $grade => $total) {
                $averages[$subject][$grade] = $counts[$subject][$grade] > 0 ? round($total / $counts[$subject][$grade], 2) : 0;
            }
        }
    }
}
