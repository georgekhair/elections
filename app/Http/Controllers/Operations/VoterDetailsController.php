<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\Voter;
use App\Models\User;

class VoterDetailsController extends Controller
{
    public function show(Voter $voter)
    {
        $voter->load([
            'assignedDelegate',
            'voterNotes.creator',
            'actionableVoterNotes',
            'relationships.relatedVoter',
            'relationships.creator',
        ]);

        $delegates = User::role('delegate')->orderBy('name')->get();

        return view('operations.voters.show', compact('voter', 'delegates'));
    }
}
