<?php

namespace App\Http\Controllers\Field;

use App\Http\Controllers\Controller;
use App\Services\TargetListService;
use App\Models\Voter;
use Illuminate\Http\Request;
class TargetController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $query = Voter::query()
            ->where('is_voted', false)
            ->where(function($q){
                $q->whereNull('last_contacted_at')
                ->orWhere('last_contacted_at', '<', now()->subMinutes(1));
            })
            ->with([
                'voterNotes' => function ($q) {
                    $q->where('requires_action', 1)->latest()->limit(3);
                }
            ]);

        // 🎯 delegate → فقط ناخبيه
        if ($user->hasRole('delegate')) {
            $query->where('assigned_delegate_id', $user->id);
        }

        // 🎯 supervisor → حسب المركز
        if ($user->hasRole('supervisor')) {
            $query->where('polling_center_id', $user->polling_center_id);
        }

        // 🎯 admin → يرى الكل (بدون filter)

        $targets = $query
            ->orderByRaw("
            CASE
                WHEN EXISTS (
                    SELECT 1 FROM voter_notes
                    WHERE voter_id = voters.id AND requires_action = 1
                ) THEN 1
                WHEN support_status = 'undecided' THEN 2
                WHEN support_status = 'supporter' AND priority_level = 'high' THEN 3
                ELSE 4
            END
        ")
            ->limit(50)
            ->get();

        return view('field.targets.index', compact('targets'));
    }

    public function markContacted(Request $request, $voterId)
    {
        $voter = Voter::findOrFail($voterId);

        $data = $request->validate([
            'result' => 'required|string',
            'note' => 'nullable|string'
        ]);

        // 🔥 LOG
        $voter->contactLogs()->create([
            'voter_id' => $voter->id,
            'user_id' => auth()->id(),
            'result' => $data['result'],
            'note' => $data['note']
        ]);

        // 🧠 DECISION ENGINE
        switch ($data['result']) {

            case 'convinced':
                $voter->support_status = 'supporter';
                $voter->priority_level = 'low';
                break;

            case 'no_answer':
                $voter->priority_level = 'high';
                break;

            case 'follow_up':
                $voter->priority_level = 'high';
                break;

            case 'rejected':
                $voter->support_status = 'opposed';
                $voter->priority_level = 'low';
                break;
        }

        // 📝 تحويل note إلى system note
        if ($data['note']) {
            $voter->voterNotes()->create([
                'type' => 'contact',
                'content' => $data['note'],
                'priority' => $data['result'] === 'follow_up' ? 'high' : 'medium',
                'requires_action' => $data['result'] === 'follow_up'
            ]);
        }

        $voter->last_contacted_at = now();
        $voter->save();

        return response()->json(['success' => true]);
    }

    public function electionMode()
    {
        $user = auth()->user();

        $query = Voter::query()
            ->where('is_voted', false)
            ->with([
                'voterNotes' => function ($q) {
                    $q->where('requires_action', 1)
                    ->latest()
                    ->limit(2);
                }
            ])
            ->where(function ($q) {
                $q->whereNull('last_contacted_at')
                ->orWhere('last_contacted_at', '<', now()->subMinutes(30));
            });

        if ($user->hasRole('delegate')) {
            $query->where('assigned_delegate_id', $user->id);
        }

        if ($user->hasRole('supervisor')) {
            $query->where('polling_center_id', $user->polling_center_id);
        }


        $targets = $query
            ->orderByRaw("
                CASE
                    WHEN EXISTS (
                        SELECT 1 FROM voter_notes
                        WHERE voter_id = voters.id AND requires_action = 1
                    ) THEN 1
                    WHEN support_status = 'undecided' THEN 2
                    WHEN support_status = 'leaning' THEN 3
                    WHEN support_status = 'supporter' AND priority_level = 'high' THEN 4
                    ELSE 5
                END
            ")
            ->limit(20)
            ->get();

        $totalVoters = Voter::visibleTo($user)->count();
        $voted = Voter::visibleTo($user)
            ->where('is_voted', true)
            ->count();

        return view('field.election-mode.index', compact(
            'targets',
            'totalVoters',
            'voted'
        ));
    }

    public function electionModeLive()
    {
        $user = auth()->user();

        $query = Voter::query()
            ->where('is_voted', false)
            ->with([
                'voterNotes' => function ($q) {
                    $q->where('requires_action', 1)
                    ->latest()
                    ->limit(2);
                }
            ])
            ->where(function ($q) {
                $q->whereNull('last_contacted_at')
                ->orWhere('last_contacted_at', '<', now()->subMinutes(30));
            });

        if ($user->hasRole('delegate')) {
            $query->where('assigned_delegate_id', $user->id);
        }

        if ($user->hasRole('supervisor')) {
            $query->where('polling_center_id', $user->polling_center_id);
        }

        $targets = $query
            ->orderByRaw("
                CASE
                    WHEN EXISTS (
                        SELECT 1 FROM voter_notes
                        WHERE voter_id = voters.id AND requires_action = 1
                    ) THEN 1
                    WHEN support_status = 'undecided' THEN 2
                    WHEN support_status = 'leaning' THEN 3
                    WHEN support_status = 'supporter' AND priority_level = 'high' THEN 4
                    ELSE 5
                END
            ")
            ->limit(20)
            ->get()
            ->map(function ($voter) {
                return [
                    'id' => $voter->id,
                    'full_name' => $voter->full_name,
                    'support_status' => $voter->support_status,
                    'priority_level' => $voter->priority_level,
                    'notes' => $voter->voterNotes->map(function ($note) {
                        return [
                            'content' => $note->content,
                            'priority' => $note->priority,
                            'type' => $note->note_type,
                        ];
                    })->values(),
                ];
            })->values();

        $totalVoters = Voter::visibleTo($user)->count();
        $voted = Voter::visibleTo($user)->where('is_voted', true)->count();

        return response()->json([
            'metrics' => [
                'total_voters' => $totalVoters,
                'voted' => $voted,
            ],
            'targets' => $targets,
            'updated_at' => now()->format('H:i:s'),
        ]);
    }

    public function electionModeSearch(Request $request)
    {
        $user = auth()->user();
        $search = trim($request->get('search', ''));

        if ($search === '' || mb_strlen($search) < 2) {
            return response()->json([]);
        }

        $query = Voter::query()
            ->where('is_voted', false)
            ->with([
                'voterNotes' => function ($q) {
                    $q->where('requires_action', 1)
                    ->latest()
                    ->limit(2);
                }
            ]);

        if ($user->hasRole('delegate')) {
            $query->where('assigned_delegate_id', $user->id);
        }

        if ($user->hasRole('supervisor')) {
            $query->where('polling_center_id', $user->polling_center_id);
        }

        $targets = $query
            ->search($search)
            ->limit(20)
            ->get()
            ->map(function ($voter) {
                return [
                    'id' => $voter->id,
                    'full_name' => $voter->full_name,
                    'support_status' => $voter->support_status,
                    'priority_level' => $voter->priority_level,
                    'notes' => $voter->voterNotes->map(function ($note) {
                        return [
                            'content' => $note->content,
                            'priority' => $note->priority,
                            'type' => $note->note_type,
                        ];
                    })->values(),
                ];
            })->values();

        return response()->json($targets);
    }
}
