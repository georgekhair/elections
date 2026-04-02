@forelse($voters as $voter)
<tr class="
    status-row
    status-{{ $voter->support_status ?? 'unknown' }}

    {{ ($voter->actionable_voter_notes_count ?? 0) > 0 ? 'has-action' : '' }}
    {{ ($voter->voter_notes_count ?? 0) > 0 ? 'has-notes' : '' }}
    {{ ($voter->relationships_count ?? 0) > 0 ? 'has-relationships' : '' }}

    priority-{{ $voter->priority_level ?? 'medium' }}
">
    <td>
        <input type="checkbox" class="row-checkbox" value="{{ $voter->id }}">
    </td>

    <td>
        <a href="{{ route('operations.voters.show', $voter->id) }}"
        style="font-weight:600; color:#2563eb; text-decoration:none; display:inline-flex; align-items:center; gap:6px; flex-wrap:wrap;">

            <span>{{ $voter->full_name }}</span>

            @if(($voter->relationships_count ?? 0) > 0)
                <span class="table-indicator table-indicator-influence" title="لديه تأثير">⭐</span>
            @endif
            @if(($voter->voter_notes_count ?? 0) > 0)
                <span class="table-indicator table-indicator-note" title="لديه ملاحظات">
                    📝
                </span>
            @endif

            @if(($voter->actionable_voter_notes_count ?? 0) > 0)
                <span class="table-indicator table-indicator-action" title="لديه ملاحظات تحتاج إجراء">
                    ⚠️
                </span>
            @endif

            @if(($voter->relationships_count ?? 0) > 0)
                <span class="table-indicator table-indicator-relationship" title="لديه علاقات / تأثير">
                    🔗
                </span>
            @endif

        </a>
    </td>

    <td>{{ $voter->pollingCenter->name ?? '-' }}</td>

    <td>
        <div class="inline-edit">
            <select onchange="updateVoter(this, {{ $voter->id }}, 'support_status')">
                <option value="supporter" @selected($voter->support_status == 'supporter')>مضمون</option>
                <option value="leaning" @selected($voter->support_status == 'leaning')>يميل</option>
                <option value="undecided" @selected($voter->support_status == 'undecided')>متردد</option>
                <option value="opposed" @selected($voter->support_status == 'opposed')>ضد</option>
                <option value="unknown" @selected($voter->support_status == 'unknown')>غير معروف</option>
            </select>
            <span class="save-status"></span>
        </div>
    </td>

    <td>
        <div class="inline-edit">
            <select onchange="updateVoter(this, {{ $voter->id }}, 'priority_level')">
                <option value="high" @selected($voter->priority_level == 'high')>عالي</option>
                <option value="medium" @selected($voter->priority_level == 'medium')>متوسط</option>
                <option value="low" @selected($voter->priority_level == 'low')>منخفض</option>
            </select>
            <span class="save-status"></span>
        </div>
    </td>

    <td>
        <div class="inline-edit">
            <select onchange="updateVoter(this, {{ $voter->id }}, 'assigned_delegate_id')">

                <option value="">—</option>

                {{-- 👇 المندوبين --}}
                <optgroup label="👥 المندوبين">
                    @foreach($delegates as $d)
                        <option value="{{ $d->id }}"
                            @selected($voter->assigned_delegate_id == $d->id)>
                            {{ $d->name }}
                        </option>
                    @endforeach
                </optgroup>

                {{-- 👇 المشرفين --}}
                <optgroup label="🧠 المشرفين">
                    @foreach($supervisors as $s)
                        <option value="supervisor_{{ $s->id }}"
                            @selected($voter->supervisor_id == $s->id && !$voter->assigned_delegate_id)>
                            {{ $s->name }} (مشرف)
                        </option>
                    @endforeach
                </optgroup>

            </select>
            <span class="save-status"></span>
        </div>
    </td>
    <td>
        <div style="display:flex; gap:4px; flex-wrap:wrap;">
            @if(($voter->voter_notes_count ?? 0) > 0)
                <span class="mini-badge mini-badge-note">ملاحظة</span>
            @endif

            @if(($voter->actionable_voter_notes_count ?? 0) > 0)
                <span class="mini-badge mini-badge-action">إجراء</span>
            @endif

            @if(($voter->relationships_count ?? 0) > 0)
                <span class="mini-badge mini-badge-relationship">علاقة</span>
            @endif
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="7">لا توجد نتائج</td>
</tr>
@endforelse
<style>
    .table-indicator{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    width:22px;
    height:22px;
    border-radius:999px;
    font-size:12px;
    line-height:1;
    border:1px solid transparent;
}

.table-indicator-note{
    background:#eff6ff;
    border-color:#bfdbfe;
}

.table-indicator-action{
    background:#fef2f2;
    border-color:#fecaca;
}

.table-indicator-relationship{
    background:#ecfdf5;
    border-color:#bbf7d0;
}

.table-indicator-influence{

    background:#ecfdf5;
    border-color:#bbf7d0;
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

.status-unknown {
    background: #f9fafb;
}

/* =========================
RIGHT BORDER INDICATOR
========================= */

.status-supporter { border-right: 4px solid #16a34a; }
.status-leaning   { border-right: 4px solid #2563eb; }
.status-undecided { border-right: 4px solid #f59e0b; }
.status-opposed   { border-right: 4px solid #dc2626; }
.status-unknown   { border-right: 4px solid #9ca3af; }

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
    box-shadow: inset 0 0 0 1px rgba(59,130,246,0.15);
}

/* =========================
HOVER FIX
========================= */

.status-row:hover {
    filter: brightness(0.94);
    box-shadow: 0 6px 14px rgba(0,0,0,0.08);
}

.status-row.updated {
    animation: flashRow 0.6s ease;
}

@keyframes flashRow {
    0% { background: #dcfce7; }
    100% { background: inherit; }
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

</style>
