@extends('layouts.admin')

@section('content')

    <div class="election-mode">

        <div class="em-header">
            <div class="em-metrics">
                <div class="em-counter">
                    تم الاقتراع: <strong id="metric-voted">{{ $voted }}</strong> /
                    <strong id="metric-total">{{ $totalVoters }}</strong>
                </div>
                @php
                    $progress = $totalVoters > 0 ? round(($voted / $totalVoters) * 100) : 0;
                @endphp
                <div class="em-progress-bar">
                    <div class="em-progress" id="progress-bar" style="width: {{ $progress }}%"></div>
                </div>
            </div>

            <div class="em-status">
                <span id="connection-status">متصل</span>
                <span id="pending-badge" style="display:none;">0</span>
                <span id="updated-at">آخر تحديث: --</span>
            </div>
        </div>

        <div class="em-search-box">
            <input type="text" id="search-input" placeholder="ابحث بالاسم أو الهوية">
        </div>

        <div id="targets-container" class="targets-container">
            @foreach($targets as $voter)
                <div class="target-card" data-id="{{ $voter->id }}">
                    <div class="top-row">
                        <div class="name">{{ $voter->full_name }}</div>
                        <div class="status-badge">{{ $voter->support_status_label }}</div>
                    </div>

                    @if($voter->priority_level === 'high')
                        <div class="priority-badge">أولوية عالية</div>
                    @endif

                    @if($voter->voterNotes->count())
                        <div class="notes">
                            @foreach($voter->voterNotes as $note)
                                <div class="note">{{ $note->content }}</div>
                            @endforeach
                        </div>
                    @endif

                    <div class="actions">
                        <button class="btn contact" data-id="{{ $voter->id }}" onclick="openContactModal(this)">
                            تم التواصل
                        </button>
                        <button class="btn vote" data-id="{{ $voter->id }}" onclick="markVoted(this)">
                            تم التصويت
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

    </div>

    <div id="contactModal" class="modal hidden">
        <div class="modal-content">
            <h3>نتيجة التواصل</h3>

            <div class="options">
                <button onclick="submitContact('convinced')">اقتنع</button>
                <button onclick="submitContact('no_answer')">لم يرد</button>
                <button onclick="submitContact('follow_up')">يحتاج متابعة</button>
                <button onclick="submitContact('rejected')">رفض</button>
            </div>

            <textarea id="contactNote" placeholder="ملاحظة اختيارية"></textarea>
            <button onclick="closeContactModal()">إغلاق</button>
        </div>
    </div>

@endsection
@section('styles')
    <style>
        .main {
            padding: 0px;
        }
        .election-mode {
            max-width: 820px;
            margin: 0 auto;
            padding: 16px;
        }

        .em-header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: #fff;
            border-radius: 14px;
            padding: 14px;
            margin-bottom: 14px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
        }

        .em-counter {
            font-size: 18px;
            margin-bottom: 8px;
        }

        .em-progress-bar {
            height: 10px;
            background: #e5e7eb;
            border-radius: 999px;
            overflow: hidden;
        }

        .em-progress {
            height: 100%;
            background: #16a34a;
        }

        .em-status {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            align-items: center;
            font-size: 13px;
            color: #475569;
        }

        .em-search-box {
            margin-bottom: 14px;
        }

        .em-search-box input {
            width: 100%;
            padding: 14px;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            font-size: 16px;
        }

        .targets-container {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .target-card {
            border-radius: 14px;
            padding: 14px;
            background: #fff;
            border-right: 8px solid #cbd5e1;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .target-card:hover {
            transform: translateY(-1px);
        }

        .top-row {
            display: flex;
            justify-content: space-between;
            align-items: start;
            gap: 10px;
        }

        .name {
            font-size: 18px;
            font-weight: 700;
        }

        .status-badge {
            font-size: 12px;
            background: #f1f5f9;
            padding: 5px 8px;
            border-radius: 8px;
            white-space: nowrap;
        }

        .priority-badge {
            margin-top: 8px;
            color: #b91c1c;
            font-size: 12px;
            font-weight: 700;
        }

        .notes {
            margin-top: 10px;
        }

        .note {
            background: #fff7ed;
            color: #9a3412;
            padding: 7px 9px;
            border-radius: 8px;
            margin-bottom: 6px;
            font-size: 13px;
        }

        .actions {
            display: flex;
            gap: 10px;
            margin-top: 12px;
        }

        .btn {
            flex: 1;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-size: 15px;
            cursor: pointer;
        }

        .btn.contact {
            background: #e2e8f0;
        }

        .btn.vote {
            background: #dc2626;
            color: #fff;
            font-weight: 700;
        }

        .modal {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .45);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .hidden {
            display: none;
        }

        .modal-content {
            background: #fff;
            width: 340px;
            max-width: 95%;
            border-radius: 14px;
            padding: 18px;
        }

        .modal-content .options button {
            width: 100%;
            margin-bottom: 8px;
            padding: 10px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
        }

        .modal-content textarea {
            width: 100%;
            min-height: 90px;
            margin-top: 10px;
            padding: 10px;
            border-radius: 10px;
            border: 1px solid #d1d5db;
        }

        .em-search-box input {
            width: 100%;
            padding: 16px 18px;
            border: 1px solid #cbd5e1;
            border-radius: 14px;
            font-size: 17px;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .em-search-box input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
        }

        .target-card.supporter {
            border-right-color: #16a34a;
            background: #f0fdf4;
        }

        .target-card.leaning {
            border-right-color: #3b82f6;
            background: #eff6ff;
        }

        .target-card.undecided {
            border-right-color: #f59e0b;
            background: #fffbeb;
        }

        .target-card.opposed {
            border-right-color: #dc2626;
            background: #fef2f2;
        }

        .target-card {
            background: #fff;
            border-radius: 18px;
            padding: 16px;
            box-shadow: 0 10px 22px rgba(0, 0, 0, 0.08);
            border-right: 8px solid #cbd5e1;
            transition: .15s ease;
            position: relative;
        }

        .target-card:hover {
            transform: translateY(-2px);
        }

        .name {
            font-size: 20px;
            font-weight: 800;
            line-height: 1.4;
        }

        .status-badge {
            font-size: 12px;
            background: #eef2ff;
            color: #334155;
            padding: 6px 10px;
            border-radius: 999px;
            white-space: nowrap;
            font-weight: 700;
        }

        .priority-badge {
            display: inline-block;
            margin-top: 8px;
            color: #b91c1c;
            background: #fee2e2;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
        }

        .notes {
            margin-top: 12px;
        }

        .note {
            background: #fff7ed;
            color: #9a3412;
            padding: 8px 10px;
            border-radius: 10px;
            margin-bottom: 7px;
            font-size: 13px;
            line-height: 1.5;
        }

        .note.high-note {
            background: #fee2e2;
            color: #991b1b;
            font-weight: 700;
        }

        .actions {
            display: flex;
            gap: 10px;
            margin-top: 14px;
        }

        .btn {
            flex: 1;
            border: none;
            border-radius: 12px;
            padding: 13px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn.contact {
            background: #e2e8f0;
            color: #0f172a;
        }

        .btn.vote {
            background: #16a34a;
            color: #fff;
        }

        .main-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* NAME */
        .name {
            font-size: 20px;
            font-weight: 800;
        }

        /* STATUS TEXT (VERY IMPORTANT) */
        .status-text {
            font-size: 13px;
            font-weight: 700;
            margin-top: 4px;
        }

        .status-text.supporter {
            color: #15803d;
        }

        .status-text.leaning {
            color: #1d4ed8;
        }

        .status-text.undecided {
            color: #b45309;
        }

        .status-text.opposed {
            color: #b91c1c;
        }

        /* ACTION BUTTONS */
        .actions {
            display: flex;
            gap: 6px;
        }

        .btn {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            border: none;
            font-size: 18px;
            cursor: pointer;
        }

        /* BUTTON COLORS */
        .btn.contact {
            background: #f1f5f9;
        }

        .btn.vote {
            background: #16a34a;
            box-shadow: 0 4px 10px rgba(22,163,74,0.25);
            color: white;
        }
        .btn:active {
            transform: scale(0.96);
        }
        /* NOTES */
        .notes {
            margin-top: 10px;
        }

        .note {
            font-size: 12px;
            background: #fff;
            padding: 6px 8px;
            border-radius: 6px;
            margin-bottom: 4px;
            border: 1px solid #e5e7eb;
        }

        /* 📱 Mobile تحسين الأزرار */
        @media (max-width: 640px) {

            .actions {
                flex-direction: column;
                /* فوق بعض بدل جنب */
                gap: 12px;
            }

            .btn {
                width: 100%;
                padding: 16px;
                /* أكبر للمس */
                font-size: 16px;
                border-radius: 14px;
            }

            /* مساحة إضافية داخل الكارد */
            .target-card {
                padding: 18px;
            }

            /* زر الاتصال + التصويت يكون بينهم مسافة واضحة */
            .btn.contact {
                margin-bottom: 6px;
            }

            /* تحسين الأيقونات إذا عندك */
            .btn::before {
                margin-left: 6px;
            }
        }
    </style>
@endsection

@section('scripts')
    <script>
        const csrfToken = '{{ csrf_token() }}';
        let currentContactButton = null;
        let currentContactVoterId = null;
        let currentTargets = [];
        let searchTerm = '';
        let searchTimeout;
        let isSearchMode = false;

        document.getElementById('search-input').addEventListener('input', function () {
            clearTimeout(searchTimeout);

            searchTimeout = setTimeout(async () => {
                searchTerm = this.value.trim();

                if (searchTerm.length < 2) {
                    isSearchMode = false;
                    renderTargets(currentTargets);
                    return;
                }

                isSearchMode = true;
                await searchElectionTargets(searchTerm);
            }, 250);
        });

        async function searchElectionTargets(query) {
            try {
                const res = await fetch(`{{ route('field.election-mode.search') }}?search=${encodeURIComponent(query)}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!res.ok) throw new Error('search failed');

                const data = await res.json();
                renderTargetsList(data);
            } catch (e) {
                console.error('Search failed:', e);
            }
        }

        function getPendingQueue() {
            return JSON.parse(localStorage.getItem('pending_actions') || '[]');
        }

        function setPendingQueue(queue) {
            localStorage.setItem('pending_actions', JSON.stringify(queue));
            updatePendingBadge();
        }

        function enqueueAction(action) {
            const queue = getPendingQueue();
            queue.push(action);
            setPendingQueue(queue);
        }

        function updatePendingBadge() {
            const badge = document.getElementById('pending-badge');
            const queue = getPendingQueue();

            if (queue.length > 0) {
                badge.style.display = 'inline-block';
                badge.textContent = `معلّق: ${queue.length}`;
            } else {
                badge.style.display = 'none';
            }
        }

        function setConnectionStatus(isOnline) {
            const el = document.getElementById('connection-status');
            el.textContent = isOnline ? 'متصل' : 'أوفلاين';
            el.style.color = isOnline ? '#16a34a' : '#dc2626';
        }

        function openContactModal(btn) {
            currentContactButton = btn;
            currentContactVoterId = btn.dataset.id;
            document.getElementById('contactModal').classList.remove('hidden');
        }

        function closeContactModal() {
            document.getElementById('contactModal').classList.add('hidden');
            document.getElementById('contactNote').value = '';
            currentContactButton = null;
            currentContactVoterId = null;
        }

        function updateMetrics(metrics) {
            document.getElementById('metric-voted').textContent = metrics.voted;
            document.getElementById('metric-total').textContent = metrics.total_voters;

            const total = Number(metrics.total_voters || 0);
            const voted = Number(metrics.voted || 0);
            const progress = total > 0 ? Math.round((voted / total) * 100) : 0;

            document.getElementById('progress-bar').style.width = progress + '%';
        }

        function renderTargets(targets) {
            currentTargets = targets;
            renderTargetsList(targets);
        }

        function renderTargetsList(targets) {
            const container = document.getElementById('targets-container');

            if (!targets.length) {
                container.innerHTML = `
                            <div class="target-card">
                                <div class="name">لا توجد نتائج</div>
                            </div>
                        `;
                return;
            }

            container.innerHTML = targets.map(voter => `
            <div class="target-card ${voter.support_status}" data-id="${voter.id}">

                <div class="main-row">

                    <div class="voter-info">
                        <div class="name">${escapeHtml(voter.full_name)}</div>
                        <div class="status-text ${voter.support_status}">
                            ${statusLabel(voter.support_status)}
                        </div>
                    </div>

                    <div class="actions">
                        <button class="btn contact" data-id="${voter.id}" onclick="openContactModal(this)">
                            📞
                        </button>
                        <button class="btn vote" data-id="${voter.id}" onclick="markVoted(this)">
                            ✔
                        </button>
                    </div>

                </div>

                ${(voter.notes || []).length ? `
                    <div class="notes">
                        ${voter.notes.map(note => `
                            <div class="note">${escapeHtml(note.content || '')}</div>
                        `).join('')}
                    </div>
                ` : ''}

            </div>
        `).join('');
        }
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text || '';
            return div.innerHTML;
        }
        function statusLabel(status) {
            switch (status) {
                case 'supporter': return 'مضمون';
                case 'leaning': return 'يميل';
                case 'undecided': return 'متردد';
                case 'opposed': return 'ضد';
                default: return 'غير معروف';
            }
        }

        async function flushQueue() {
            const queue = getPendingQueue();
            if (!queue.length) return;

            const remaining = [];

            for (const action of queue) {
                try {
                    if (action.type === 'contacted') {
                        const res = await fetch(`/field/voters/${action.voter_id}/contacted`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify(action.payload)
                        });

                        if (!res.ok) throw new Error('contact sync failed');
                    }

                    if (action.type === 'voted') {
                        const res = await fetch(`/delegate/voters/${action.voter_id}/mark`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken
                            }
                        });

                        if (!res.ok) throw new Error('vote sync failed');
                    }
                } catch (e) {
                    remaining.push(action);
                }
            }

            setPendingQueue(remaining);
        }

        async function refreshElectionMode() {
            try {
                const res = await fetch(`{{ route('field.election-mode.live') }}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!res.ok) throw new Error('live failed');

                const data = await res.json();
                updateMetrics(data.metrics);
                currentTargets = data.targets;

                if (!isSearchMode) {
                    renderTargetsList(data.targets);
                }
                document.getElementById('updated-at').textContent = 'آخر تحديث: ' + (data.updated_at || '--');
                setConnectionStatus(true);
            } catch (e) {
                setConnectionStatus(false);
            }
        }
        async function markVoted(btn) {
            const voterId = btn.dataset.id;
            const card = btn.closest('.target-card');

            card.style.opacity = '0.7';

            try {
                const res = await fetch(`/delegate/voters/${voterId}/mark`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                if (!res.ok) throw new Error('vote failed');

                card.remove();
                refreshElectionMode();
            } catch (e) {
                enqueueAction({
                    type: 'voted',
                    voter_id: voterId,
                    payload: {},
                    created_at: new Date().toISOString()
                });
                card.style.border = '2px dashed #f59e0b';
            }
        }
        async function submitContact(result) {
            const note = document.getElementById('contactNote').value;
            const voterId = currentContactVoterId;
            const card = currentContactButton.closest('.target-card');

            closeContactModal();

            card.style.opacity = '0.7';

            const payload = {
                result: result,
                note: note
            };

            try {
                const res = await fetch(`/field/voters/${voterId}/contacted`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                });

                if (!res.ok) throw new Error('contact failed');

                card.remove();
                refreshElectionMode();
            } catch (e) {
                enqueueAction({
                    type: 'contacted',
                    voter_id: voterId,
                    payload: payload,
                    created_at: new Date().toISOString()
                });

                card.style.border = '2px dashed #f59e0b';
            }
        }


        setInterval(refreshElectionMode, 10000);
        refreshElectionMode();

        window.addEventListener('online', flushQueue);
        setInterval(flushQueue, 30000);
        updatePendingBadge();
    </script>
@endsection
