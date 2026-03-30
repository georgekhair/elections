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
        $query = Voter::with([
            'delegate',
            'pollingCenter',
            'actionableVoterNotes:id,voter_id,note_type,priority,requires_action',
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
        //$query = Voter::with(['delegate', 'pollingCenter']);
        $query = Voter::with([
            'delegate',
            'pollingCenter',
            'actionableVoterNotes:id,voter_id,note_type,priority,requires_action',
        ]);

        $this->applyFilters($query, $request);

        $totals = $this->getTotals($query);

        $voters = $query->limit(50)->get();
        $delegates = User::role('delegate')->orderBy('name')->get();

        return response()->json([
            'html' => view('operations.data-preparation.partials.table-rows', compact('voters','delegates'))->render(),
            'totals' => $totals
        ]);
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

    private function applyFilters($query, Request $request): void
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

            $query->where(function ($q) use ($search, $words) {
                if (is_numeric($search)) {
                    $q->orWhereRaw('CAST(national_id AS CHAR) LIKE ?', ['%' . $search . '%']);
                }

                foreach ($words as $word) {
                    if (!$word) {
                        continue;
                    }

                    $q->where(function ($qq) use ($word) {
                        $qq->where('full_name', 'like', '%' . $word . '%')
                           ->orWhereRaw('CAST(national_id AS CHAR) LIKE ?', ['%' . $word . '%']);
                    });
                }
            });
        }
    }

    private function getTotals($query)
    {
        $clone = clone $query;

        return $clone->selectRaw("
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

        $voters = Voter::where(function ($q) use ($query) {
                $q->where('full_name', 'like', "%{$query}%")
                ->orWhere('national_id', 'like', "%{$query}%")
                ->orWhere('voter_no', $query);
            })
            ->limit(10)
            ->get(['id', 'full_name', 'national_id']);

        return response()->json($voters);
    }
}
