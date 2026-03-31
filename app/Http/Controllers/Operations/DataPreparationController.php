<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\PollingCenter;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Http\Request;

class DataPreparationController extends Controller
{
    public function index(Request $request)
    {
        //$query = Voter::with(['delegate', 'pollingCenter']);
        $query = Voter::with(['delegate', 'pollingCenter'])
        ->withCount([
            'voterNotes',
            'relationships',
            'actionableVoterNotes',
        ]);
        $this->applyFilters($query, $request);

        $totals = $this->getTotals($query);

        $voters = $query->paginate(50)->withQueryString();

        $centers = PollingCenter::orderBy('name')->get();
        $delegates = User::role('delegate')->orderBy('name')->get();

        return view('operations.data-preparation.index', compact('voters', 'centers', 'delegates', 'totals'));
    }

    public function search(Request $request)
    {
        try {

            $query = Voter::with(['delegate', 'pollingCenter'])
                ->withCount([
                    'voterNotes',
                    'relationships',
                    'actionableVoterNotes',
                ]);

            $this->applyFilters($query, $request);

            $totals = $this->getTotals($query);

            $voters = $query->limit(50)->get();

            $delegates = User::role('delegate')->orderBy('name')->get();

            return response()->json([
                'html' => view('operations.data-preparation.partials.table-rows', compact('voters','delegates'))->render(),
                'totals' => $totals
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Voter $voter)
    {
        $request->validate([
            'support_status' => 'nullable|in:supporter,leaning,undecided,opposed,unknown',
            'priority_level' => 'nullable|in:high,medium,low',
            'assigned_delegate_id' => 'nullable|exists:users,id',
        ]);

        $data = $request->only([
            'support_status',
            'priority_level',
            'assigned_delegate_id',
        ]);

        $data = array_filter($data, fn ($v) => !is_null($v));

        $voter->update($data);

        return response()->json([
            'success' => true,
            'updated' => $data,
        ]);
    }

    public function bulkAssign(Request $request)
    {
        $request->validate([
            'voter_ids' => 'required|array|min:1',
            'voter_ids.*' => 'exists:voters,id',
            'assigned_delegate_id' => 'required|exists:users,id',
        ]);

        if ($request->voter_ids === 'ALL') {

            $query = Voter::query();

            // 🔥 apply SAME filters as search/index
            if ($request->center_id) {
                $query->where('polling_center_id', $request->center_id);
            }

            if ($request->status) {
                $query->where('support_status', $request->status);
            }

            if ($request->priority) {
                $query->where('priority_level', $request->priority);
            }

            if ($request->delegate_id) {
                $query->where('assigned_delegate_id', $request->delegate_id);
            }

            if ($request->unassigned) {
                $query->whereNull('assigned_delegate_id');
            }

            if ($request->target) {
                $query->where(function ($q) {
                    $q->whereIn('support_status', ['leaning', 'undecided'])
                    ->orWhere(function ($qq) {
                        $qq->where('support_status', 'supporter')
                            ->where('priority_level', 'high');
                    });
                });
            }

            if ($request->name) {
                $words = array_filter(explode(' ', trim($request->name)));

                $query->where(function ($q) use ($words) {
                    foreach ($words as $word) {
                        $q->where(function ($qq) use ($word) {
                            $qq->where('full_name', 'like', "%$word%")
                            ->orWhere('national_id', 'like', "%$word%");
                        });
                    }
                });
            }

            $ids = $query->pluck('id');

        } else {
            $ids = $request->voter_ids;
        }

        Voter::whereIn('id', $ids)->update([
            'assigned_delegate_id' => $request->assigned_delegate_id,
        ]);

        return back()->with('success', 'تم توزيع الناخبين على المندوب بنجاح');
    }

    public function bulkStatus(Request $request)
    {
        $request->validate([
            'voter_ids' => 'required|array|min:1',
            'voter_ids.*' => 'exists:voters,id',
            'support_status' => 'nullable|in:supporter,leaning,undecided,opposed,unknown',
            'priority_level' => 'nullable|in:high,medium,low',
        ]);

        $payload = [];

        if ($request->filled('support_status')) {
            $payload['support_status'] = $request->support_status;
        }

        if ($request->filled('priority_level')) {
            $payload['priority_level'] = $request->priority_level;
        }

        if (!empty($payload)) {
            if ($request->voter_ids === 'ALL') {

                $query = Voter::query();

                // SAME filters again 🔥
                if ($request->center_id) {
                    $query->where('polling_center_id', $request->center_id);
                }

                if ($request->status) {
                    $query->where('support_status', $request->status);
                }

                if ($request->priority) {
                    $query->where('priority_level', $request->priority);
                }

                if ($request->delegate_id) {
                    $query->where('assigned_delegate_id', $request->delegate_id);
                }

                if ($request->unassigned) {
                    $query->whereNull('assigned_delegate_id');
                }

                if ($request->target) {
                    $query->where(function ($q) {
                        $q->whereIn('support_status', ['leaning', 'undecided'])
                        ->orWhere(function ($qq) {
                            $qq->where('support_status', 'supporter')
                                ->where('priority_level', 'high');
                        });
                    });
                }

                if ($request->name) {
                    $words = array_filter(explode(' ', trim($request->name)));

                    $query->where(function ($q) use ($words) {
                        foreach ($words as $word) {
                            $q->where(function ($qq) use ($word) {
                                $qq->where('full_name', 'like', "%$word%")
                                ->orWhere('national_id', 'like', "%$word%");
                            });
                        }
                    });
                }

                $ids = $query->pluck('id');

            } else {
                $ids = $request->voter_ids;
            }

            if (!empty($payload)) {
                Voter::whereIn('id', $ids)->update($payload);
            }
        }

        return back()->with('success', 'تم تحديث الناخبين المحددين بنجاح');
    }

    private function applyFilters($query, Request $request, $withRanking = true): void
    {
        if ($request->filled('center_id')) {
            $query->where('polling_center_id', $request->center_id);
        }

        if ($request->filled('status')) {
            $query->where('support_status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority_level', $request->priority);
        }

        if ($request->boolean('unassigned')) {
            $query->whereNull('assigned_delegate_id');
        } elseif ($request->filled('delegate_id')) {
            $query->where('assigned_delegate_id', $request->delegate_id);
        }

        if ($request->boolean('target')) {
            $query->where(function ($q) {
                $q->whereIn('support_status', ['leaning', 'undecided'])
                  ->orWhere(function ($qq) {
                      $qq->where('support_status', 'supporter')
                         ->where('priority_level', 'high');
                  });
            });
        }

        if ($request->filled('name')) {

            $search = trim($request->name);
            $words = preg_split('/\s+/', $search);

            // =========================
            // FILTER (keep results)
            // =========================
            $query->where(function ($q) use ($search, $words) {

                // exact phrase
                $q->orWhere('full_name', 'like', "%{$search}%");

                // numeric search
                if (is_numeric($search)) {
                    $q->orWhereRaw('CAST(national_id AS CHAR) LIKE ?', ["%{$search}%"]);
                }

                // word-by-word
                foreach ($words as $word) {
                    if (!$word) continue;

                    $q->orWhere('full_name', 'like', "%{$word}%")
                    ->orWhereRaw('CAST(national_id AS CHAR) LIKE ?', ["%{$word}%"]);
                }
            });

            // =========================
            // RANKING (SAFE VERSION) 🔥
            // =========================

            if ($withRanking) {

                $bindings = [];
                $scoreSqlParts = [];

                $scoreSqlParts[] = "CASE WHEN full_name LIKE ? THEN 100 ELSE 0 END";
                $bindings[] = "%{$search}%";

                $scoreSqlParts[] = "CASE WHEN full_name LIKE ? THEN 70 ELSE 0 END";
                $bindings[] = "%" . implode('%', $words) . "%";

                foreach ($words as $word) {
                    $scoreSqlParts[] = "CASE WHEN full_name LIKE ? THEN 20 ELSE 0 END";
                    $bindings[] = "%{$word}%";
                }

                $scoreSqlParts[] = "CASE WHEN CAST(national_id AS CHAR) LIKE ? THEN 50 ELSE 0 END";
                $bindings[] = "%{$search}%";

                $query->selectRaw("
                    voters.*,
                    (" . implode(' + ', $scoreSqlParts) . ") as relevance_score
                ", $bindings);

                $query->orderByDesc('relevance_score');
            }
        }

        // =========================
        // NOTES FILTERS
        // =========================

        if ($request->boolean('has_notes')) {
            $query->has('voterNotes');
        }

        if ($request->boolean('needs_action')) {
            $query->whereHas('voterNotes', function ($q) {
                $q->where('requires_action', 1);
            });
        }

        if ($request->boolean('high_priority_notes')) {
            $query->whereHas('voterNotes', function ($q) {
                $q->where('priority', 'high');
            });
        }

        // =========================
        // RELATIONSHIPS FILTERS
        // =========================

        if ($request->boolean('has_relationships')) {
            $query->has('relationships');
        }

        if ($request->boolean('has_influencer')) {
            $query->whereHas('relationships', function ($q) {
                $q->where('is_primary_influencer', 1);
            });
        }
    }

    private function getTotals($query)
    {
        // 🔥 STEP 1: create a clean base query (NO withCount, NO select pollution)
        $base = Voter::query();

        // 🔥 STEP 2: copy ONLY filters (not the mutated builder)
        $this->applyFilters($base, request(), false);

        // 🔥 STEP 3: run aggregate safely
        return $base->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN support_status = 'supporter' THEN 1 ELSE 0 END) as supporter,
            SUM(CASE WHEN support_status = 'leaning' THEN 1 ELSE 0 END) as leaning,
            SUM(CASE WHEN support_status = 'undecided' THEN 1 ELSE 0 END) as undecided,
            SUM(CASE WHEN support_status = 'opposed' THEN 1 ELSE 0 END) as opposed,
            SUM(CASE WHEN support_status = 'unknown' THEN 1 ELSE 0 END) as unknown
        ")->first();
    }

    public function searchSimple(Request $request)
    {
        $query = trim($request->q);

        if (!$query || strlen($query) < 2) {
            return response()->json([]);
        }

        $voters = Voter::selectRaw("
                id, full_name, national_id,

                (
                    (CASE WHEN full_name LIKE ? THEN 100 ELSE 0 END)
                    +
                    (CASE WHEN full_name LIKE ? THEN 70 ELSE 0 END)
                    +
                    (CASE WHEN CAST(national_id AS CHAR) LIKE ? THEN 50 ELSE 0 END)
                ) as relevance_score
            ", [
                "%{$query}%",
                "%" . str_replace(' ', '%', $query) . "%",
                "%{$query}%"
            ])
            ->where(function ($q2) use ($query) {
                $q2->where('full_name', 'like', "%{$query}%")
                ->orWhere('national_id', 'like', "%{$query}%")
                ->orWhere('voter_no', $query);
            })
            ->orderByDesc('relevance_score')
            ->limit(10)
            ->get();

        return response()->json($voters);
    }
}
