<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\PollingCenter;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Http\Request;
use App\Services\VoterAssignmentService;

class DataPreparationController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Voter::visibleTo($user)
            ->forUserFamilies($user)
            ->with([
                'delegate',
                'supervisor',
                'assignedUser',
                'pollingCenter',
                'relationships' => function ($q) {
                    $q->select('id', 'voter_id', 'related_voter_id', 'related_name')
                        ->with(['relatedVoter:id,full_name'])
                        ->take(3); // مهم للأداء
                },
                // 🔥 إضافة الملاحظات
                'voterNotes' => function ($q) {
                    $q->latest()
                        ->select('id', 'voter_id', 'content', 'note_type', 'priority', 'requires_action', 'created_at')
                        ->take(3);
                }
            ])
            ->withCount([
                'voterNotes',
                'relationships',
                'actionableVoterNotes',
            ]);

        $this->applyFilters($query, $request);

        $totals = $this->getTotals($request);

        $voters = $query->paginate(50)->withQueryString();

        $centers = PollingCenter::orderBy('name')->get();
        $delegates = User::role('delegate')->orderBy('name')->get();
        $supervisors = User::role('supervisor')->orderBy('name')->get();
        $families = Voter::query()
            ->whereNotNull('family_name')
            ->where('family_name', '!=', '')
            ->distinct()
            ->orderBy('family_name')
            ->pluck('family_name');

        if ($request->ajax()) {
            return response()->json([
                'html' => view('operations.data-preparation.partials.table-rows', compact('voters', 'delegates', 'supervisors'))->render(),
                'pagination' => $voters->links()->toHtml(),
                'pagination_info' => 'عرض ' . $voters->firstItem() . ' إلى ' . $voters->lastItem() . ' من ' . $voters->total(),
                'totals' => $totals,
            ]);
        }

        return view('operations.data-preparation.index', compact(
            'voters',
            'centers',
            'delegates',
            'supervisors',
            'totals',
            'families'
        ));
    }

    public function search(Request $request)
    {
        try {
            $user = auth()->user();

            $query = Voter::visibleTo($user)
                ->forUserFamilies($user)
                ->with([
                    'delegate',
                    'supervisor',
                    'assignedUser',
                    'pollingCenter',
                    'relationships' => function ($q) {
                        $q->select('id', 'voter_id', 'related_voter_id', 'related_name')
                            ->with(['relatedVoter:id,full_name'])
                            ->take(3); // مهم للأداء
                    },

                    'voterNotes' => function ($q) {
                        $q->latest()
                            ->select('id', 'voter_id', 'content', 'note_type', 'priority', 'requires_action', 'created_at')
                            ->take(3);
                    }
                ])
                ->withCount([
                    'voterNotes',
                    'relationships',
                    'actionableVoterNotes',
                ]);

            $this->applyFilters($query, $request);

            $totals = $this->getTotals($request);

            $voters = $query->paginate(50)->withQueryString();

            $delegates = User::role('delegate')->orderBy('name')->get();

            $supervisors = User::role('supervisor')->orderBy('name')->get();

            return response()->json([
                'html' => view('operations.data-preparation.partials.table-rows', compact('voters', 'delegates', 'supervisors'))->render(),
                'pagination' => $voters->links()->toHtml(),
                'pagination_info' => 'عرض ' . $voters->firstItem() . ' إلى ' . $voters->lastItem() . ' من ' . $voters->total(),
                'totals' => $totals,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Voter $voter, VoterAssignmentService $assignmentService)
    {
        $user = auth()->user();

        if (
            !$voter->newQuery()
                ->visibleTo($user)
                ->forUserFamilies($user)
                ->where('id', $voter->id)
                ->exists()
        ) {
            abort(403);
        }

        $request->validate([
            'support_status' => 'nullable|in:supporter,leaning,undecided,opposed,unknown,traveling',
            'priority_level' => 'nullable|in:high,medium,low',
            'assigned_delegate_id' => 'nullable',
        ]);

        $data = [];

        if ($request->has('assigned_delegate_id')) {
            $payload = $assignmentService->buildPayloadFromSelectionValue(
                (string) $request->assigned_delegate_id
            );

            $data = array_merge($data, $payload);
        }

        if ($request->filled('support_status')) {
            $data['support_status'] = $request->support_status;
        }

        if ($request->filled('priority_level')) {
            $data['priority_level'] = $request->priority_level;
        }

        $voter->update($data);

        return response()->json([
            'success' => true,
            'updated' => $data,
        ]);
    }

    public function bulkAssign(Request $request, VoterAssignmentService $assignmentService)
    {
        $request->validate([
            'voter_ids' => [
                'required',
                function ($attribute, $value, $fail) {
                    if ($value !== 'ALL' && !is_array($value)) {
                        $fail('صيغة الناخبين غير صحيحة');
                    }
                },
            ],
            'assigned_delegate_id' => 'nullable',
            'supervisor_id' => 'nullable|exists:users,id',
        ]);

        if (!$request->filled('assigned_delegate_id') && !$request->filled('supervisor_id')) {
            return back()->withErrors(['يجب اختيار مندوب أو مشرف']);
        }

        if ($request->voter_ids === 'ALL') {
            $user = auth()->user();

            $query = Voter::query()
                ->forUserFamilies($user);

            $this->applyBulkFilters($query, $request);
            $ids = $query->pluck('id');
        } else {
            $ids = $request->voter_ids;
        }

        $selectionValue = '';

        if ($request->filled('supervisor_id')) {
            $selectionValue = 'supervisor_' . $request->supervisor_id;
        } elseif ($request->filled('assigned_delegate_id')) {
            $selectionValue = (string) $request->assigned_delegate_id;
        }

        $assignmentService->bulkAssignBySelectionValue($ids, $selectionValue);

        return back()->with('success', 'تم توزيع الناخبين بنجاح');
    }

    public function bulkStatus(Request $request)
    {
        $request->validate([
            'voter_ids' => 'required|array|min:1',
            'voter_ids.*' => 'exists:voters,id',
            'support_status' => 'nullable|in:supporter,leaning,undecided,opposed,unknown,traveling',
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

                $user = auth()->user();

                $query = Voter::query()
                    ->forUserFamilies($user);

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

                // ✅ FAMILY FILTER (NEW)
                if ($request->family_name) {
                    $query->where('family_name', $request->family_name);
                }

                if ($request->delegate_id) {
                    $delegateFilter = (string) $request->delegate_id;

                    if (str_starts_with($delegateFilter, 'supervisor_')) {
                        $query->where('supervisor_id', str_replace('supervisor_', '', $delegateFilter));
                    } else {
                        $query->where('assigned_delegate_id', $delegateFilter);
                    }
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

    private function applyBulkFilters($query, Request $request): void
    {
        if ($request->center_id) {
            $query->where('polling_center_id', $request->center_id);
        }

        if ($request->status) {
            $query->where('support_status', $request->status);
        }

        if ($request->priority) {
            $query->where('priority_level', $request->priority);
        }

        if ($request->family_name) {
            $query->where('family_name', $request->family_name);
        }

        if ($request->delegate_id) {
            $delegateFilter = (string) $request->delegate_id;

            if (str_starts_with($delegateFilter, 'supervisor_')) {

                $supervisorId = str_replace('supervisor_', '', $delegateFilter);

                // 🔥 خيار التحكم
                $includeDelegates = $request->boolean('include_delegates');

                if ($includeDelegates) {

                    // ✅ المشرف + المندوبين تحته
                    $delegateIds = User::role('delegate')
                        ->where('supervisor_id', $supervisorId)
                        ->pluck('id');

                    $query->where(function ($q) use ($supervisorId, $delegateIds) {
                        $q->where('supervisor_id', $supervisorId)
                            ->orWhereIn('assigned_delegate_id', $delegateIds);
                    });

                } else {

                    // ✅ فقط المشرف مباشرة
                    $query->where('supervisor_id', $supervisorId)
                        ->whereNull('assigned_delegate_id');

                }
            }
        }

        if ($request->unassigned) {
            $query->whereNull('assigned_delegate_id')
                ->whereNull('supervisor_id')
                ->whereNull('assigned_user_id');
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
                        $qq->where('full_name', 'like', "%{$word}%")
                            ->orWhere('national_id', 'like', "%{$word}%");
                    });
                }
            });
        }

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

        if ($request->boolean('has_relationships')) {
            $query->has('relationships');
        }

        if ($request->boolean('has_influencer')) {
            $query->whereHas('relationships', function ($q) {
                $q->where('is_primary_influencer', 1);
            });
        }
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
        // ✅ FAMILY FILTER (NEW)
        if ($request->filled('family_name')) {
            $query->where('family_name', $request->family_name);
        }
        if ($request->boolean('unassigned')) {
            $query->whereNull('assigned_delegate_id')
                ->whereNull('supervisor_id')
                ->whereNull('assigned_user_id');
         } elseif ($request->filled('delegate_id')) {

        $value = (string) $request->delegate_id;

        // =========================
        // SUPERVISOR
        // =========================
        if (str_starts_with($value, 'supervisor_')) {

            $supervisorId = str_replace('supervisor_', '', $value);
            $includeDelegates = $request->boolean('include_delegates');

            if ($includeDelegates) {

                $delegateIds = User::role('delegate')
                    ->where('supervisor_id', $supervisorId)
                    ->pluck('id');

                $query->where(function ($q) use ($supervisorId, $delegateIds) {
                    $q->where(function ($qq) use ($supervisorId) {
                        $qq->where('supervisor_id', $supervisorId)
                           ->whereNull('assigned_delegate_id');
                    })
                    ->orWhereIn('assigned_delegate_id', $delegateIds);
                });

            } else {

                $query->where('supervisor_id', $supervisorId)
                      ->whereNull('assigned_delegate_id');
            }

        } else {

            // =========================
            // ✅ DELEGATE (FIX)
            // =========================
            $query->where('assigned_delegate_id', $value);
        }
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
                    if (!$word)
                        continue;

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

    private function getTotals(Request $request)
    {
        $user = auth()->user();

        $base = Voter::query()
            ->forUserFamilies($user);

        $this->applyFilters($base, $request, false);

        return $base->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN support_status = 'supporter' THEN 1 ELSE 0 END) as supporter,
            SUM(CASE WHEN support_status = 'leaning' THEN 1 ELSE 0 END) as leaning,
            SUM(CASE WHEN support_status = 'undecided' THEN 1 ELSE 0 END) as undecided,
            SUM(CASE WHEN support_status = 'opposed' THEN 1 ELSE 0 END) as opposed,
            SUM(CASE WHEN support_status = 'traveling' THEN 1 ELSE 0 END) as traveling,
            SUM(CASE WHEN support_status = 'unknown' THEN 1 ELSE 0 END) as unknown
        ")->first();
    }

    public function searchSimple(Request $request)
    {
        $search = trim($request->q);

        if (!$search || strlen($search) < 2) {
            return response()->json([]);
        }

        $words = preg_split('/\s+/', $search);

        // =========================
        // BUILD SCORE (SMART)
        // =========================

        $bindings = [];
        $scoreParts = [];

        // 🔥 exact full match
        $scoreParts[] = "CASE WHEN full_name LIKE ? THEN 120 ELSE 0 END";
        $bindings[] = "%{$search}%";

        // 🔥 phrase match (words together)
        $scoreParts[] = "CASE WHEN full_name LIKE ? THEN 90 ELSE 0 END";
        $bindings[] = "%" . implode('%', $words) . "%";

        // 🔥 individual words match
        foreach ($words as $word) {
            if (!$word)
                continue;

            $scoreParts[] = "CASE WHEN full_name LIKE ? THEN 30 ELSE 0 END";
            $bindings[] = "%{$word}%";

            $scoreParts[] = "CASE WHEN first_name LIKE ? THEN 20 ELSE 0 END";
            $bindings[] = "%{$word}%";

            $scoreParts[] = "CASE WHEN father_name LIKE ? THEN 15 ELSE 0 END";
            $bindings[] = "%{$word}%";

            $scoreParts[] = "CASE WHEN grandfather_name LIKE ? THEN 10 ELSE 0 END";
            $bindings[] = "%{$word}%";

            $scoreParts[] = "CASE WHEN family_name LIKE ? THEN 25 ELSE 0 END";
            $bindings[] = "%{$word}%";
        }

        // 🔥 national id match
        $scoreParts[] = "CASE WHEN CAST(national_id AS CHAR) LIKE ? THEN 80 ELSE 0 END";
        $bindings[] = "%{$search}%";

        // 🔥 voter number exact
        if (is_numeric($search)) {
            $scoreParts[] = "CASE WHEN voter_no = ? THEN 100 ELSE 0 END";
            $bindings[] = $search;
        }

        $scoreSql = implode(' + ', $scoreParts);

        // =========================
        // QUERY
        // =========================
        $user = auth()->user();

        $voters = Voter::forUserFamilies($user)
            ->selectRaw("
                id,
                full_name,
                national_id,
                voter_no,
                ({$scoreSql}) as relevance_score
            ", $bindings)

            ->where(function ($q) use ($search, $words) {

                // full phrase
                $q->where('full_name', 'like', "%{$search}%");

                // words
                foreach ($words as $word) {
                    if (!$word)
                        continue;

                    $q->orWhere('full_name', 'like', "%{$word}%")
                        ->orWhere('first_name', 'like', "%{$word}%")
                        ->orWhere('father_name', 'like', "%{$word}%")
                        ->orWhere('grandfather_name', 'like', "%{$word}%")
                        ->orWhere('family_name', 'like', "%{$word}%")
                        ->orWhere('national_id', 'like', "%{$word}%");
                }

                // numeric search
                if (is_numeric($search)) {
                    $q->orWhere('national_id', 'like', "%{$search}%")
                        ->orWhere('voter_no', $search);
                }
            })

            ->orderByDesc('relevance_score')
            ->limit(10)
            ->get();

        return response()->json($voters);
    }

    public function notes(Voter $voter)
    {
        // 🔐 مهم: تأكد من الصلاحيات
        if (!$voter->newQuery()->visibleTo(auth()->user())->where('id', $voter->id)->exists()) {
            abort(403);
        }

        return response()->json(
            $voter->voterNotes()
                ->latest()
                ->take(20)
                ->get()
                ->map(function ($note) {
                    return [
                        'note' => $note->content,
                        'type' => $note->note_type,
                        'priority' => $note->priority,
                        'requires_action' => $note->requires_action,
                        'created_at' => $note->created_at->diffForHumans(),
                    ];
                })
        );
    }
}
