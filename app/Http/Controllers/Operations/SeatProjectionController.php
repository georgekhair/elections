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
        $totalVotes = array_sum($votes);

        $projection = $service->allocate($votes, 13, 5);
        $ourList = $lists->firstWhere('is_our_list', true);

        $ourListVotes = $ourList?->estimated_votes ?? 0;
        $ourListSeats = $projection['seats'][$ourList->name] ?? 0;

        // default target (can be changed from request)
        $targetSeats = request('target_seats', $ourListSeats + 1);

        $votesNeeded = null;

        if ($ourList) {

            $simulatedVotes = $votes;

            // 🔥 increase votes until we reach target
            for ($i = 0; $i <= 20000; $i += 50) {

                $simulatedVotes[$ourList->name] = $ourListVotes + $i;

                $simProjection = $service->allocate($simulatedVotes, 13, 5);

                $seats = $simProjection['seats'][$ourList->name] ?? 0;

                if ($seats >= $targetSeats) {
                    $votesNeeded = $ourListVotes + $i;
                    break;
                }
            }
        }
        return view('operations.seat-projection.index', compact(
            'lists',
            'projection',
            'totalVotes',
            'ourList',
            'ourListVotes',
            'ourListSeats',
            'targetSeats',
            'votesNeeded'
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
