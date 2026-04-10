<div class="targets-container">

    @foreach($targets as $voter)

        <div class="target-card {{ $voter->support_status }} {{ $voter->priority_level == 'high' ? 'high-priority' : '' }}">

            <div class="top-row">
                <div class="name">{{ $voter->full_name }}</div>

                <div class="status-badge">
                    @if($voter->support_status == 'supporter')
                        🟢 مضمون
                    @elseif($voter->support_status == 'leaning')
                        🔵 يميل
                    @elseif($voter->support_status == 'undecided')
                        🟡 متردد
                    @else
                        ⚪ غير معروف
                    @endif
                </div>
            </div>
             @if($voter->priority_level == 'high')
                <div class="priority">🔥 أولوية عالية</div>
            @endif
            {{-- ⚠️ NOTES --}}
            @if($voter->voterNotes->count())
                <div class="notes">
                    @foreach($voter->voterNotes as $note)
                        <div class="note {{ $note->priority == 'high' ? 'high' : '' }}">
                            {{ getNoteIcon($note->type) }} {{ $note->content }}
                        </div>
                    @endforeach
                </div>
            @endif
            <div class="actions">

                <button class="btn contact"
                    data-id="{{ $voter->id }}"
                    onclick="markContacted(this)">
                    📞 تم التواصل
                </button>

                <button class="btn vote"
                        onclick="markVoted({{ $voter->id }})">
                    ✔ تم التصويت
                </button>

            </div>

        </div>

    @endforeach

</div>

<div id="contactModal" class="modal hidden">

    <div class="modal-content">

        <h3>نتيجة التواصل</h3>

        <div class="options">
            <button onclick="submitContact('convinced')">✅ اقتنع</button>
            <button onclick="submitContact('no_answer')">❌ لم يرد</button>
            <button onclick="submitContact('follow_up')">⚠️ يحتاج متابعة</button>
            <button onclick="submitContact('rejected')">🚫 رفض</button>
        </div>

        <textarea id="contactNote" placeholder="ملاحظة (اختياري)"></textarea>

        <button onclick="closeModal()">إغلاق</button>

    </div>

</div>

@php
function getNoteIcon($type){
    return match($type){
        'persuasion' => '🧠',
        'mobilization' => '🚗',
        'issue' => '⚠️',
        'support' => '💚',
        default => '📝',
    };
}
@endphp
<style>
    .targets-container {
    max-width: 700px;
    margin: auto;
}

.target-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 16px;
    margin-bottom: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    transition: 0.2s;
}

.target-card:hover {
    transform: translateY(-2px);
}

/* TOP */
.top-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.name {
    font-size: 18px;
    font-weight: bold;
}

/* STATUS COLORS */
.target-card.supporter {
    border-right: 6px solid #16a34a;
}

.target-card.leaning {
    border-right: 6px solid #3b82f6;
}

.target-card.undecided {
    border-right: 6px solid #f59e0b;
}

/* BADGE */
.status-badge {
    font-size: 13px;
    background: #f1f5f9;
    padding: 4px 8px;
    border-radius: 6px;
}

/* ACTIONS */
.actions {
    display: flex;
    gap: 10px;
    margin-top: 12px;
}

.btn {
    flex: 1;
    padding: 12px;
    border-radius: 8px;
    border: none;
    font-size: 14px;
    cursor: pointer;
}

/* BUTTON COLORS */
.btn.contact {
    background: #e5e7eb;
}

.btn.vote {
    background: #dc2626;
    color: white;
    font-weight: bold;
}

/* CLICK EFFECT */
.btn:active {
    transform: scale(0.97);
}

.priority {
    margin-top: 6px;
    font-size: 12px;
    font-weight: bold;
    color: #dc2626;
}

/* NOTES */
.notes {
    margin-top: 8px;
}

.note {
    font-size: 13px;
    padding: 6px 8px;
    margin-bottom: 4px;
    border-radius: 6px;
    background: #fff7ed;
    color: #9a3412;
}

.note.high {
    background: #fee2e2;
    color: #991b1b;
    font-weight: bold;
}

/* MODAL */
.modal {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.6);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.hidden {
    display: none;
}

.modal-content {
    background: white;
    padding: 20px;
    border-radius: 12px;
    width: 320px;
    text-align: center;
}

.options button {
    width: 100%;
    margin: 6px 0;
    padding: 10px;
    border-radius: 8px;
    border: none;
    font-size: 16px;
    cursor: pointer;
}
</style>

<script>

function markVoted(id){

    const btn = event.currentTarget;

    fetch(`/delegate/voters/${id}/mark`, {
        method:'POST',
        headers:{
            'X-CSRF-TOKEN':'{{ csrf_token() }}'
        }
    }).then(() => {

        const card = btn.closest('.target-card');

        card.style.background = '#dcfce7';
        card.innerHTML = '<div style="color:green;font-weight:bold">✔ تم تسجيل التصويت</div>';


    });
}

/* MODAL */
let currentVoterId = null;
function markContacted(btn){
    currentVoterId = btn.dataset.id;
    window.currentButton = btn;

    document.getElementById('contactModal').classList.remove('hidden');
}
function closeModal(){
    document.getElementById('contactModal').classList.add('hidden');
}
function submitContact(result){

    const note = document.getElementById('contactNote').value;

    fetch(`/field/voters/${currentVoterId}/contacted`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            result: result,
            note: note
        })
    })
    .then(async res => {

        if (!res.ok) {
            const text = await res.text();
            throw new Error(text);
        }

        return res.json().catch(() => ({})); // 🔥 مهم
    })
    .then(() => {

        closeModal();

        const card = window.currentButton.closest('.target-card');

        card.style.background = '#ecfdf5';
        card.style.opacity = 0.7;
        card.style.transform = 'scale(0.98)';

        setTimeout(() => {
            card.style.opacity = 0;
            card.style.height = 0;
        }, 300);

    })
    .catch(err => {
        console.error(err);
        alert('فشل الإرسال');
    });
}
</script>
