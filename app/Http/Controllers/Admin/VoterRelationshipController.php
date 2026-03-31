<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voter;
use Illuminate\Http\Request;

class VoterRelationshipController extends Controller
{
    public function store(Request $request, Voter $voter)
    {
        $validated = $request->validate([
            'related_voter_id' => 'nullable|exists:voters,id',
            'related_name' => 'nullable|string|max:255',
            'relationship_type' => 'required|in:spouse,son,daughter,brother,sister,father,mother,relative,friend,neighbor,influencer,other',
            'influence_level' => 'required|in:low,medium,high',
            'is_primary_influencer' => 'required|boolean',
            'notes' => 'nullable|string|max:3000',
        ]);

        if (empty($validated['related_voter_id']) && empty($validated['related_name'])) {
            return back()->withErrors([
                'related_voter_id' => 'Choose a voter or enter a temporary related person name.',
            ])->withInput();
        }

        if (!empty($validated['related_voter_id']) && (int) $validated['related_voter_id'] === (int) $voter->id) {
            return back()->withErrors([
                'related_voter_id' => 'A voter cannot be related to themselves.',
            ])->withInput();
        }

        $validated['voter_id'] = $voter->id;
        $validated['created_by'] = auth()->id();
        $validated['is_unconfirmed'] = empty($validated['related_voter_id']);

        $voter->relationships()->create($validated);

        return back()->with('success', 'Voter relationship added successfully.');
    }
}
