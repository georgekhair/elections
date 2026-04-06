<?php

namespace App\Http\Controllers\Delegate;

use App\Http\Controllers\Controller;
use App\Models\Voter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VoterController extends Controller
{

    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Voter::visibleTo($user)
            ->search($request->search);

        $voters = $query
            ->orderBy('full_name')
            ->limit(30)
            ->get();

        return view('delegate.voters', compact('voters'));
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
            abort(403);
        }

        if ($voter->is_voted) {

            return back()->with('error', 'تم تسجيل هذا الناخب مسبقاً.');

        }

        $voter->update([
            'is_voted' => true,
            'voted_at' => now(),
            'voted_by' => $user->id
        ]);


        return back()->with('success', 'تم تسجيل الاقتراع بنجاح.');
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
}
