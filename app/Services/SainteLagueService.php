<?php

namespace App\Services;

class SainteLagueService
{
    /**
     * Allocate seats using Sainte-Laguë.
     *
     * @param array $votes Example: ['List A' => 2100, 'List B' => 1800]
     * @param int $seats Number of seats to allocate
     * @param float $thresholdPercent Threshold percentage (e.g. 5)
     * @return array
     */
    public function allocate(array $votes, int $seats = 13, float $thresholdPercent = 5): array
    {
        $totalVotes = array_sum($votes);

        if ($totalVotes <= 0) {
            return [
                'qualified' => [],
                'threshold_votes' => 0,
                'quotients' => [],
                'top_quotients' => [],
                'seats' => [],
            ];
        }

        $thresholdVotes = ($thresholdPercent / 100) * $totalVotes;

        // Step 1: filter qualified lists
        $qualified = [];
        foreach ($votes as $list => $count) {
            if ((int) $count >= $thresholdVotes) {
                $qualified[$list] = (int) $count;
            }
        }

        // Step 2: build quotient table
        $quotients = [];
        foreach ($qualified as $list => $count) {
            for ($divisor = 1; $divisor <= ($seats * 2); $divisor += 2) {
                $quotients[] = [
                    'list' => $list,
                    'votes' => $count,
                    'divisor' => $divisor,
                    'quotient' => $count / $divisor,
                ];
            }
        }

        // Step 3: sort descending
        usort($quotients, function ($a, $b) {
            if ($a['quotient'] == $b['quotient']) {
                return $b['votes'] <=> $a['votes'];
            }
            return $b['quotient'] <=> $a['quotient'];
        });

        // Step 4: take top N quotients
        $topQuotients = array_slice($quotients, 0, $seats);

        // Step 5: allocate seats
        $seatAllocation = [];
        foreach ($qualified as $list => $count) {
            $seatAllocation[$list] = 0;
        }

        foreach ($topQuotients as $row) {
            $seatAllocation[$row['list']]++;
        }

        return [
            'qualified' => $qualified,
            'threshold_votes' => $thresholdVotes,
            'quotients' => $quotients,
            'top_quotients' => $topQuotients,
            'seats' => $seatAllocation,
        ];
    }
}
