<?php

namespace App\Services;

use App\Models\Voter;

class TargetListService
{
    public function getForDelegate($delegateId, $limit = 50)
    {
        return Voter::where('assigned_delegate_id', $delegateId)
            ->where('is_voted', false)
            ->where(function ($q) {
                $q->whereIn('support_status', ['undecided', 'leaning'])
                  ->orWhere(function ($q2) {
                      $q2->where('support_status', 'supporter')
                         ->where('priority_level', 'high');
                  });
            })
            ->orderByRaw("
                CASE
                    WHEN support_status = 'undecided' THEN 1
                    WHEN support_status = 'leaning' THEN 2
                    WHEN support_status = 'supporter' THEN 3
                    ELSE 4
                END
            ")
            ->orderByRaw("
                CASE
                    WHEN priority_level = 'high' THEN 1
                    WHEN priority_level = 'medium' THEN 2
                    ELSE 3
                END
            ")
            ->limit($limit)
            ->get();
    }
}
