<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\Voter;

class VoterDetailsController extends Controller
{
    public function show(Voter $voter)
    {
        $voter->load([
            'assignedDelegate',
            'voterNotes.creator',
            'actionableVoterNotes',
            'relationships.relatedVoter',
        ]);

        return view('operations.voters.show', compact('voter'));
    }
}
