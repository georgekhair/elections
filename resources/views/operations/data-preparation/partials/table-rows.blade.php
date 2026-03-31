@forelse($voters as $voter)
<tr style="{{ ($voter->actionable_voter_notes_count ?? 0) > 0 ? 'background:#fff7ed;' : '' }}">
    <td>
        <input type="checkbox" class="row-checkbox" value="{{ $voter->id }}">
    </td>

    <td>
        <a href="{{ route('operations.voters.show', $voter->id) }}"
        style="font-weight:600; color:#2563eb; text-decoration:none; display:inline-flex; align-items:center; gap:6px; flex-wrap:wrap;">

            <span>{{ $voter->full_name }}</span>

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
                @foreach($delegates as $d)
                    <option value="{{ $d->id }}" @selected($voter->assigned_delegate_id == $d->id)>
                        {{ $d->name }}
                    </option>
                @endforeach
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
</style>
