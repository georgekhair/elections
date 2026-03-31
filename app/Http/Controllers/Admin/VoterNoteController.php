<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voter;
use App\Models\VoterNote;
use Illuminate\Http\Request;

class VoterNoteController extends Controller
{
    public function store(Request $request, Voter $voter)
    {
        $validated = $request->validate([
            'note_type' => 'required|in:general,transportation,persuasion,health,contact,risk,family,influencer',
            'content' => 'required|string|max:5000',
            'priority' => 'required|in:low,medium,high',
            'requires_action' => 'required|boolean',
            'action_due_at' => 'nullable|date',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['voter_id'] = $voter->id;

        $voter->voterNotes()->create($validated);

        return back()->with('success', 'Voter note added successfully.');
    }
    public function update(Request $request, VoterNote $voterNote)
    {
        $validated = $request->validate([
            'note_type' => 'required|in:general,transportation,persuasion',
            'priority' => 'nullable|in:low,medium,high',
            'requires_action' => 'required|boolean',
            'content' => 'required|string|max:3000',
            'action_due_at' => 'nullable|date',
        ]);

        $voterNote->update($validated);

        return back()->with('success', 'Voter note updated successfully.');
    }
    public function destroy(VoterNote $voterNote)
    {
        $voterNote->delete();

        return back()->with('success', 'Voter note deleted successfully.');
    }
}
