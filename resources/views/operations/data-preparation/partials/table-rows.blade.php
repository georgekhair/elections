@forelse($voters as $voter)
<tr>
    <td>
        <input type="checkbox" class="row-checkbox" value="{{ $voter->id }}">
    </td>

    <td>
    <a href="{{ route('operations.voters.show', $voter->id) }}"
       style="font-weight:600; color:#2563eb; text-decoration:none;">

        {{ $voter->full_name }}

        {{-- 🔴 indicator لو في action --}}
        @if(($voter->actionableVoterNotes ?? collect())->count())
            <span style="color:#dc2626;">●</span>
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
</tr>
@empty
<tr>
    <td colspan="6">لا توجد نتائج</td>
</tr>
@endforelse
