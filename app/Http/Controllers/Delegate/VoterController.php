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

        $query = Voter::where('polling_center_id', $user->polling_center_id);

        if ($request->filled('search')) {

            $search = trim($request->search);

            $query->where(function ($q) use ($search) {

                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('national_id', 'like', "%{$search}%")
                  ->orWhere('voter_no', $search);

            });
        }

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

        $voter = Voter::where('polling_center_id', $user->polling_center_id)
            ->where('id', $voterId)
            ->firstOrFail();


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
}
