<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\ElectionList;
use App\Models\SeatProjectionSnapshot;
use App\Services\SainteLagueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SeatProjectionController extends Controller
{
    public function index(SainteLagueService $service)
    {
        $lists = ElectionList::orderBy('id')->get();

        $votes = $lists->pluck('estimated_votes', 'name')->toArray();

        $projection = $service->allocate($votes, 13, 5);

        return view('operations.seat-projection.index', compact(
            'lists',
            'projection'
        ));
    }

    public function updateVotes(Request $request, SainteLagueService $service)
    {
        $validated = $request->validate([
            'votes' => ['required', 'array'],
            'votes.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $lists = ElectionList::all();

        foreach ($lists as $list) {
            if (isset($validated['votes'][$list->id])) {
                $list->update([
                    'estimated_votes' => (int) $validated['votes'][$list->id],
                ]);
            }
        }

        $freshLists = ElectionList::orderBy('id')->get();
        $votes = $freshLists->pluck('estimated_votes', 'name')->toArray();

        $projection = $service->allocate($votes, 13, 5);

        SeatProjectionSnapshot::create([
            'input_votes' => $votes,
            'projected_seats' => $projection['seats'],
            'created_by' => Auth::id(),
        ]);

        return redirect()
            ->route('operations.seat-projection.index')
            ->with('success', 'تم تحديث التقديرات وحساب المقاعد بنجاح.');
    }
}
