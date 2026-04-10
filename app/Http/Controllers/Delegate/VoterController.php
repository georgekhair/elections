<?php

namespace App\Http\Controllers\Delegate;

use App\Http\Controllers\Controller;
use App\Models\Voter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VoterController extends Controller
{

    public function index()
{
    $user = auth()->user();

    // ✅ stats
    $totalVoters = Voter::visibleTo($user)->count();

    $voted = Voter::visibleTo($user)
        ->where('is_voted', true)
        ->count();

    // ✅ priority (اختياري لكن مهم)
    $priorityVoters = Voter::visibleTo($user)
        ->where('is_voted', false)
        ->orderByRaw("
            CASE
                WHEN EXISTS (
                    SELECT 1 FROM voter_notes
                    WHERE voter_notes.voter_id = voters.id
                    AND requires_action = 1
                ) THEN 1
                WHEN support_status IN ('undecided','leaning') THEN 2
                WHEN support_status = 'supporter' AND priority_level = 'high' THEN 3
                ELSE 4
            END
        ")
        ->limit(10)
        ->get();

    return view('delegate.voters', compact(
        'totalVoters',
        'voted',
        'priorityVoters'
    ));
}


    public function markVoted($voterId)
    {
        $user = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | CRITICAL SECURITY
        |--------------------------------------------------------------------------
        | We only fetch voters from the delegate's center.
        | This prevents URL manipulation attacks.
        |--------------------------------------------------------------------------
        */

        $voter = Voter::visibleTo($user)
            ->where('id', $voterId)
            ->firstOrFail();

        // 🔒 strict ownership
        if ($voter->assigned_delegate_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($voter->is_voted) {

            return response()->json(['error' => 'تم تسجيل هذا الناخب مسبقاً.'], 400);

        }

        $voter->update([
            'is_voted' => true,
            'voted_at' => now(),
            'voted_by' => $user->id
        ]);


        return response()->json([
            'success' => true
        ]);
    }

    public function show(Voter $voter)
    {
        $voter->load([
            'assignedDelegate',
            'notes.creator',
            'relationships.relatedVoter',
            'relationships.creator',
        ]);

        return view('admin.voters.show', compact('voter'));
    }

    public function search(Request $request)
    {
        $user = auth()->user();

        $voters = Voter::visibleTo($user)
            ->search($request->search)
            ->limit(20)
            ->get();

        return response()->json($voters);
    }

    public function priority()
    {
        $user = auth()->user();

        $voters = Voter::visibleTo($user)
            ->where('is_voted', false)
            ->orderByRaw("
                CASE
                    WHEN EXISTS (
                        SELECT 1 FROM voter_notes
                        WHERE voter_notes.voter_id = voters.id
                        AND requires_action = 1
                    ) THEN 1
                    WHEN support_status IN ('undecided','leaning') THEN 2
                    WHEN support_status = 'supporter' AND priority_level = 'high' THEN 3
                    ELSE 4
                END
            ")
            ->limit(5)
            ->get()
            ->map(function ($v) {
                return [
                    'id' => $v->id,
                    'full_name' => $v->full_name,
                    'support_status' => $v->support_status,

                    // 🔥 هنا الجديد
                    'issues' => $v->voterNotes->map(function ($note) {
                        return [
                            'text' => $note->content,
                            'type' => $note->note_type,
                            'priority' => $note->priority,
                        ];
                    }),
                ];
            });

        return response()->json($voters);
    }
}
