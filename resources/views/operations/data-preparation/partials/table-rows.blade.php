@forelse($voters as $voter)

    @php
        $user = auth()->user();

        $canEdit = true;

        if ($user->hasRole('data_operator')) {

            $canEdit = \App\Models\Voter::forUserFamilies($user)
                ->where('id', $voter->id)
                ->exists();
        }
    @endphp

    <tr class="
            status-row
            status-{{ $voter->support_status ?? 'unknown' }}
            {{ !$canEdit ? 'readonly-row' : '' }}
            {{ ($voter->actionable_voter_notes_count ?? 0) > 0 ? 'has-action' : '' }}
            {{ ($voter->voter_notes_count ?? 0) > 0 ? 'has-notes' : '' }}
            {{ ($voter->relationships_count ?? 0) > 0 ? 'has-relationships' : '' }}

            priority-{{ $voter->priority_level ?? 'medium' }}

        ">
        <td>
            <input type="checkbox" class="row-checkbox" value="{{ $voter->id }}">
        </td>

        <td>
            @php
                $user = auth()->user();
            @endphp

            @if($user->hasRole('data_operator'))
                <span style="font-weight:600; color:#6b7280; display:inline-flex; align-items:center; gap:6px; flex-wrap:wrap; cursor:not-allowed;">
            @else
                <a href="{{ route('operations.voters.show', $voter->id) }}"
                style="font-weight:600; color:#2563eb; text-decoration:none; display:inline-flex; align-items:center; gap:6px; flex-wrap:wrap;">
            @endif

                <span>{{ $voter->full_name }}</span>

                @if(($voter->relationships_count ?? 0) > 0)
                    <span class="table-indicator table-indicator-influence" title="لديه تأثير">⭐</span>
                @endif

                @if(($voter->voter_notes_count ?? 0) > 0)
                    <span class="table-indicator table-indicator-note" title="لديه ملاحظات">📝</span>
                @endif

                @if(($voter->actionable_voter_notes_count ?? 0) > 0)
                    <span class="table-indicator table-indicator-action" title="لديه ملاحظات تحتاج إجراء">⚠️</span>
                @endif

                @if(($voter->relationships_count ?? 0) > 0)
                    <span class="table-indicator table-indicator-relationship" title="لديه علاقات / تأثير">🔗</span>
                @endif

            @if($user->hasRole('data_operator'))
                </span>
            @else
                </a>
            @endif
        </td>

        <td>{{ $voter->pollingCenter->name ?? '-' }}</td>

        <td>
            <div class="inline-edit">
                <select
                    @disabled(!$canEdit)
                    onchange="updateVoter(this, {{ $voter->id }}, 'support_status')">
                    <option value="supporter" @selected($voter->support_status == 'supporter')>مضمون</option>
                    <option value="leaning" @selected($voter->support_status == 'leaning')>يميل</option>
                    <option value="undecided" @selected($voter->support_status == 'undecided')>متردد</option>
                    <option value="opposed" @selected($voter->support_status == 'opposed')>ضد</option>
                    <option value="traveling" @selected($voter->support_status == 'traveling')>مسافر</option>
                    <option value="unknown" @selected($voter->support_status == 'unknown')>غير معروف</option>
                </select>
                <span class="save-status"></span>
            </div>
        </td>

        <td>
            <div class="inline-edit">
                <select
                    @disabled(!$canEdit)
                    onchange="updateVoter(this, {{ $voter->id }}, 'priority_level')">
                    <option value="high" @selected($voter->priority_level == 'high')>عالي</option>
                    <option value="medium" @selected($voter->priority_level == 'medium')>متوسط</option>
                    <option value="low" @selected($voter->priority_level == 'low')>منخفض</option>
                </select>
                <span class="save-status"></span>
            </div>
        </td>

        <td>
            <div class="inline-edit">
                <select
                    @disabled(!$canEdit)
                    onchange="updateVoter(this, {{ $voter->id }}, 'assigned_delegate_id')">

                    <option value="">—</option>

                    {{-- 👇 المندوبين --}}
                    <optgroup label="👥 المندوبين">
                        @foreach($delegates as $d)
                            <option value="{{ $d->id }}" @selected($voter->assigned_delegate_id == $d->id)>
                                {{ $d->name }}
                            </option>
                        @endforeach
                    </optgroup>

                    {{-- 👇 المشرفين --}}
                    <optgroup label="🧠 المشرفين">
                        @foreach($supervisors as $s)
                            <option value="supervisor_{{ $s->id }}" @selected($voter->supervisor_id == $s->id && !$voter->assigned_delegate_id)>
                                {{ $s->name }} (مشرف)
                            </option>
                        @endforeach
                    </optgroup>

                </select>
                <span class="save-status"></span>
            </div>
        </td>
        <td class="insights-cell">

            {{-- Notes --}}
            @if($voter->voterNotes->count())

    @foreach($voter->voterNotes->take(2) as $note)

        <div class="note-row {{ $note->priority === 'high' ? 'high' : '' }}">

            <span class="note-icon">
                @switch($note->note_type)
                    @case('persuasion') 🧠 @break
                    @case('mobilization') 🚗 @break
                    @case('issue') ⚠️ @break
                    @case('support') 💚 @break
                    @default 📝
                @endswitch
            </span>

            <span class="note-text" title="{{ $note->content }}">
                {{ \Illuminate\Support\Str::limit($note->content, 50) }}
            </span>

        </div>

    @endforeach

    @if($voter->voterNotes->count() > 2)
        <div class="note-more">
            +{{ $voter->voterNotes->count() - 2 }} أكثر
        </div>
    @endif

@endif

            {{-- Relationships --}}
            @if($voter->relationships->count())
                <div class="insight-item">
                    <span class="icon">🔗</span>

                    <span>
                        @foreach($voter->relationships as $rel)
                            <span class="relation-name">
    @if($rel->relatedVoter && $rel->relatedVoter->full_name)
        {{ $rel->relatedVoter->full_name }}
    @elseif(!empty($rel->related_name))
        {{ $rel->related_name }}
    @else
        —
    @endif
</span>@if(!$loop->last)، @endif
                        @endforeach

                        @if($voter->relationships_count > 3)
                            <span class="more-relations">
                                +{{ $voter->relationships_count - 3 }}
                            </span>
                        @endif
                    </span>
                </div>
            @endif

            {{-- Flags --}}
            @if($voter->actionable_voter_notes_count > 0)
                <div class="insight-item danger">
                    ⚠️ يحتاج متابعة
                </div>
            @endif

        </td>
    </tr>
@empty
    <tr>
        <td colspan="7">لا توجد نتائج</td>
    </tr>
@endforelse
<div id="notes-popover" class="notes-popover hidden"></div>
<style>
    .table-indicator {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        border-radius: 999px;
        font-size: 12px;
        line-height: 1;
        border: 1px solid transparent;
    }

    .table-indicator-note {
        background: #eff6ff;
        border-color: #bfdbfe;
    }

    .table-indicator-action {
        background: #fef2f2;
        border-color: #fecaca;
    }

    .table-indicator-relationship {
        background: #ecfdf5;
        border-color: #bbf7d0;
    }

    .table-indicator-influence {

        background: #ecfdf5;
        border-color: #bbf7d0;
    }

    /* =========================
STATUS COLORS (BASE)
========================= */

    .status-supporter {
        background: #f0fdf4;
    }

    .status-leaning {
        background: #eff6ff;
    }

    .status-undecided {
        background: #fffbeb;
    }

    .status-opposed {
        background: #fef2f2;
    }

    .status-traveling {
        background: #ffedd5;
        color: #9a3412;
    }

    .status-unknown {
        background: #f9fafb;
    }

    /* =========================
RIGHT BORDER INDICATOR
========================= */

    .status-supporter {
        border-right: 4px solid #16a34a;
    }

    .status-leaning {
        border-right: 4px solid #2563eb;
    }

    .status-undecided {
        border-right: 4px solid #f59e0b;
    }

    .status-opposed {
        border-right: 4px solid #dc2626;
    }

    .status-traveling {
        border-right: 4px solid #f97316;
    }

    .status-unknown {
        border-right: 4px solid #9ca3af;
    }

    /* =========================
ACTION OVERRIDE (🔥 مهم)
========================= */

    .has-action {
        background: #fff7ed !important;
        border-right: 4px solid #f97316 !important;
    }

    /* =========================
NOTES (خفيف)
========================= */

    .has-notes:not(.has-action) {
        box-shadow: inset 0 0 0 1px rgba(59, 130, 246, 0.15);
    }

    /* =========================
HOVER FIX
========================= */

    .status-row:hover {
        filter: brightness(0.94);
        box-shadow: 0 6px 14px rgba(0, 0, 0, 0.08);
    }

    .status-row.updated {
        animation: flashRow 0.6s ease;
    }

    @keyframes flashRow {
        0% {
            background: #dcfce7;
        }

        100% {
            background: inherit;
        }
    }

    .status-row {
        position: relative;
        transition: all 0.2s ease;
    }

    .status-row::after {
        position: absolute;
        inset: 0;
        background: transparent;
        pointer-events: none;
        border-radius: 8px;
    }

    .insights-cell {
        font-size: 13px;
        line-height: 1.6;
    }

    .insight-item {
        display: flex;
        align-items: center;
        gap: 5px;
        margin-bottom: 3px;
        color: #374151;
    }

    .insight-item .icon {
        font-size: 13px;
    }

    .insight-item .highlight {
        color: #dc2626;
        font-weight: 600;
    }

    .insight-item.danger {
        color: #dc2626;
        font-weight: 600;
    }



    .insight-item {
    display: flex;
    align-items: flex-start;
    gap: 6px;
    font-size: 13px;
    margin-bottom: 4px;
}

.insight-item .icon {
    font-size: 14px;
    margin-top: 2px;
}

.insight-item.danger {
    color: #dc2626;
    font-weight: 600;
}

.insight-item.more {
    color: #2563eb;
    font-size: 12px;
    cursor: pointer;
}

    .note-item {
        padding: 10px;
        border-bottom: 1px solid #eee;
    }

    .note-item:last-child {
        border-bottom: none;
    }

    .note-item.high {
        background: #fee2e2;
    }

    .note-meta {
        font-size: 11px;
        color: #666;
        margin-top: 5px;
    }

    .clickable {
        cursor: pointer;
    }

    .clickable:hover {
        text-decoration: underline;
    }

    .note-row {
    display: flex;
    align-items: flex-start;
    gap: 6px;

    font-size: 12px;
    padding: 5px 6px;

    border-radius: 6px;
    background: #f9fafb;

    transition: all 0.15s ease;
}

.note-row:hover {
    background: #f1f5f9;
}

.note-row.high {
    background: #fee2e2;
    border-left: 3px solid #dc2626;
}

.note-icon {
    font-size: 13px;
    flex-shrink: 0;
    margin-top: 2px;
}

.note-text {
    color: #374151;
    line-height: 1.4;

    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;

    overflow: hidden;
}

.note-more {
    font-size: 11px;
    color: #2563eb;
    margin-top: 2px;
    cursor: pointer;
}

.relation-name {
    font-weight: 500;
    color: #1f2937;
}

.more-relations {
    color: #2563eb;
    font-size: 12px;
    margin-right: 4px;
}
.readonly-row {
    opacity: 0.65;
    background: #f9fafb;
}

.readonly-row select {
    background: #e5e7eb;
    cursor: not-allowed;
}
</style>
