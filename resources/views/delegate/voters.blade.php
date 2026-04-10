@extends('layouts.admin')

@section('content')

    <div class="war-container">

        {{-- 🔴 HEADER --}}
        <div class="war-header">

            <div class="counter">
                تم الاقتراع: {{ $voted }} من {{ $totalVoters }}
            </div>
            @php
                $progress = $totalVoters > 0 ? ($voted / $totalVoters) * 100 : 0;
            @endphp
            <div class="progress-bar">
                <div class="progress" style="width: {{ ($voted / $totalVoters) * 100 }}%"></div>
            </div>

        </div>

        {{-- 🔥 PRIORITY TARGETS --}}
        <div class="priority-section">

            <h2>🔥 المطلوب الآن</h2>

            <div class="priority-list" id="priority-list"></div>

        </div>

        {{-- 🔍 SEARCH --}}
        <input id="search" class="search-input" placeholder="بحث سريع..." autofocus>

        {{-- 🎛 FILTERS --}}
        <div class="tabs">
            <button onclick="filter('priority')">🔥 أولوية</button>
            <button onclick="filter('undecided')">متردد</button>
            <button onclick="filter('supporter')">مضمون</button>
            <button onclick="filter('all')">الكل</button>
        </div>

        {{-- 📋 RESULTS --}}
        <div id="results"></div>

    </div>

@endsection

@section('scripts')

    <script>

        let timer = null;

        document.getElementById('search').addEventListener('input', function () {

            clearTimeout(timer);

            let query = this.value;

            if (query.length < 1) {
                document.getElementById('results').innerHTML = '';
                return;
            }

            timer = setTimeout(() => {

                fetch(`{{ route('delegate.voters.search') }}?search=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {

                        let html = '';

                        data.forEach(voter => {

                            html += `
                        <div class="voter-card ${voter.support_status}" data-id="${voter.id}">

                            <div class="top">
                                <div class="name">${highlight(voter.full_name, query)}</div>
                                <div class="status">${getStatusLabel(voter.support_status)}</div>
                            </div>

                            <div class="meta">${voter.national_id}</div>

                            ${voter.has_issue ? '<div class="alert">⚠️ يحتاج متابعة</div>' : ''}

                            ${voter.is_voted
                                    ? '<div class="voted">✔ تم الاقتراع</div>'
                                    : `<button onclick="vote(${voter.id})" class="vote-btn">اقتراع</button>`
                                }

                        </div>
                        `;

                        });

                        document.getElementById('results').innerHTML = html;

                    });

            }, 300); // debounce

        });

        function getStatusLabel(status) {
            switch (status) {
                case 'supporter': return 'مضمون';
                case 'leaning': return 'يميل';
                case 'undecided': return 'متردد';
                case 'opposed': return 'ضد';
                default: return 'غير معروف';
            }
        }

        function vote(id) {

            const card = document.querySelector(`[data-id="${id}"]`);

            fetch(`/delegate/voters/${id}/mark`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
                .then(res => {

                    if (!res.ok) {
                        throw new Error('Request failed');
                    }

                    return res.text(); // لأنك ترجع redirect حالياً
                })
                .then(() => {

                    if (card) {
                        card.classList.add('voted-success');
                        card.innerHTML += '<div class="voted">✔ تم</div>';
                        card.style.transition = '0.3s';
                        card.style.opacity = '0';
                        setTimeout(() => card.remove(), 300);
                    }

                    loadPriority();

                })
                .catch(err => {
                    console.error('Vote failed:', err);
                    alert('فشل تسجيل الاقتراع');
                });
        }
        setInterval(() => {
            loadPriority();
        }, 5000);
        function loadPriority() {

            fetch(`{{ route('delegate.priority') }}`, {
                headers: {
                    'Accept': 'application/json'
                }
            })
                .then(res => {

                    if (!res.ok) {
                        throw new Error('Server error');
                    }

                    return res.json();
                })
                .then(data => {

                    if (!Array.isArray(data)) {
                        throw new Error('Invalid JSON format');
                    }

                    let html = data.map(v => `
                    <div class="priority-card">

                        <div class="name">${v.full_name}</div>

                        <div class="badges">
                            ${v.support_status === 'undecided' ? '<span class="tag yellow">متردد</span>' : ''}
                            ${v.support_status === 'leaning' ? '<span class="tag blue">يميل</span>' : ''}
                            ${v.issues.length > 0
    ? v.issues.map(n => `
        <div class="issue ${n.priority === 'high' ? 'danger' : ''}">
            ${getNoteIcon(n.type)} ${n.text}
        </div>
    `).join('')
    : ''
}
                        </div>

                        <button onclick="vote(${v.id})">✔ تم</button>

                    </div>
                `).join('');

                    document.getElementById('priority-list').innerHTML = html;

                })
                .catch(err => {
                    console.error('Priority load failed:', err);

                    // 🔥 لا تخرب الصفحة
                    document.getElementById('priority-list').innerHTML =
                        '<div style="color:#f87171">⚠️ فشل تحديث القائمة</div>';
                });

        }
        function escapeRegExp(string) {
            return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }

        function highlight(text, query) {

            if (!query) return text;

            try {
                const safeQuery = escapeRegExp(query);

                return text.replace(
                    new RegExp(safeQuery, 'gi'),
                    match => `<mark>${match}</mark>`
                );

            } catch (e) {
                return text; // fallback بدون كسر الصفحة
            }
        }
        function getNoteIcon(type) {
    switch(type){
        case 'persuasion': return '🧠';
        case 'mobilization': return '🚗';
        case 'issue': return '⚠️';
        case 'support': return '💚';
        default: return '📝';
    }
}
        loadPriority();
        document.getElementById('search').focus();
    </script>
    <style>
        body {
    font-family: "Segoe UI", Tahoma, Arial, sans-serif;
    background: #0f172a;
    color: #e5e7eb;
    line-height: 1.5;
    direction: rtl;
}
        .search-input {
            width: 100%;
    padding: 14px;
    font-size: 18px;
    border-radius: 10px;
    border: none;
    margin-bottom: 15px;
    background: #1f2937;
    color: white;
        }

        .voter-card {
    background: #1f2937;
    padding: 12px;
    margin-bottom: 10px;
    border-radius: 10px;
}

        .name {
    font-size: 16px;
    font-weight: bold;
    margin-bottom: 6px;
}

        .meta {
            font-size: 13px;
            color: #666;
        }

        .vote-btn {
            width: 100%;
            margin-top: 10px;
            padding: 14px;
            font-size: 18px;
            background: #dc2626;
            color: white;
            border: none;
            border-radius: 8px;
        }

        .voted {
            margin-top: 10px;
            color: green;
            font-weight: bold;
        }

        /* HEADER */
        .war-header {
    position: sticky;
    top: 0;
    background: #111827;
    padding: 14px;
    z-index: 100;
    border-bottom: 1px solid #1f2937;
}

.counter {
    font-size: 22px;
    font-weight: bold;
    text-align: center;
}

.progress-bar {
    height: 8px;
    background: #1f2937;
    border-radius: 6px;
    margin-top: 6px;
}

.progress {
    height: 100%;
    background: linear-gradient(90deg, #22c55e, #16a34a);
}

        /* PRIORITY */
        .priority-section {
    background: #111827;
    padding: 12px;
    border-radius: 10px;
    margin-bottom: 12px;
}

        .priority-card {
    background: #1f2937;
    padding: 12px;
    border-radius: 10px;
    margin-bottom: 8px;
    border-right: 5px solid #dc2626;
    box-shadow: 0 0 0 rgba(220,38,38,0.4);
    animation: pulseRed 2s infinite;
}

@keyframes pulseRed {
    0% { box-shadow: 0 0 0 0 rgba(220,38,38,0.5); }
    70% { box-shadow: 0 0 0 8px rgba(220,38,38,0); }
    100% { box-shadow: 0 0 0 0 rgba(220,38,38,0); }
}
        .priority-card button {
            width: 100%;
    margin-top: 10px;
    padding: 12px;
    font-size: 16px;
    background: #22c55e;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: bold;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.85; }
            100% { opacity: 1; }
        }
        /* CARDS */


        .voter-card.undecided {
    border-right: 4px solid #f59e0b;
}

.voter-card.supporter {
    border-right: 4px solid #22c55e;
}

.voter-card.leaning {
    border-right: 4px solid #3b82f6;
}

        /* BUTTON */
        .vote-btn {
            width: 100%;
            padding: 14px;
            font-size: 18px;
            background: #dc2626;
            border-radius: 8px;
        }

        /* SUCCESS */
        .voted-success {
    background: #14532d !important;
    opacity: 0.6;
    transform: scale(0.98);
}

        .tag {
            padding: 2px 6px;
            border-radius: 6px;
            font-size: 11px;
            margin-right: 4px;
        }

        .tag.yellow {
            background: #f59e0b;
        }

        .tag.blue {
            background: #3b82f6;
        }

        .tag.red {
            background: #dc2626;
        }
        .issue {
    font-size: 12px;
    margin-top: 4px;
    padding: 5px 7px;
    border-radius: 6px;
    background: rgba(248,113,113,0.15);
    color: #fca5a5;
}

.issue.danger {
    background: rgba(220,38,38,0.25);
    font-weight: bold;
}
    </style>
@endsection
