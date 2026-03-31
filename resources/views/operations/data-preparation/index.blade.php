@extends('layouts.admin')

@section('content')

<h1>تجهيز بيانات الناخبين</h1>

{{-- Active Filters --}}
@if(request()->hasAny(['center_id', 'status', 'priority', 'delegate_id', 'unassigned', 'has_notes', 'needs_action', 'high_priority_notes', 'has_relationships', 'has_influencer']))
<div class="card" style="margin-bottom:15px;">
    <strong>الفلاتر الحالية:</strong>

    @if(request('center_id'))
        <span class="badge badge-operations">مركز محدد</span>
    @endif

    @if(request('status'))
        <span class="badge badge-supervisor">الحالة: {{ request('status') }}</span>
    @endif

    @if(request('priority'))
        <span class="badge badge-admin">الأولوية: {{ request('priority') }}</span>
    @endif

    @if(request('delegate_id'))
        <span class="badge badge-delegate">مندوب محدد</span>
    @endif

    @if(request('unassigned'))
        <span class="badge badge-admin">غير موزعين</span>
    @endif
    @if(request('has_notes'))
        <span class="badge">📝 لديه ملاحظات</span>
    @endif

    @if(request('needs_action'))
        <span class="badge">🚨 يحتاج إجراء</span>
    @endif

    @if(request('high_priority_notes'))
        <span class="badge">🔥 ملاحظات عالية</span>
    @endif

    @if(request('has_relationships'))
        <span class="badge">🔗 علاقات</span>
    @endif

    @if(request('has_influencer'))
        <span class="badge">⭐ مؤثر</span>
    @endif
</div>
@endif

<div class="card">

{{-- Filters --}}
<div class="top-bar">

    {{-- ===== MAIN FILTERS ===== --}}
    <div class="filter-group">

        {{-- Quick Search --}}
        <input type="text"
               id="quick-search"
               autocomplete="off"
               placeholder="🔎 ابحث بالاسم أو الهوية..."
               oninput="liveSearch()">

        {{-- Center --}}
        <select name="center_id" onchange="liveSearch()">
            <option value="">كل المراكز</option>
            @foreach($centers as $center)
                <option value="{{ $center->id }}" @selected(request('center_id') == $center->id)>
                    {{ $center->name }}
                </option>
            @endforeach
        </select>

        {{-- Status --}}
        <select name="status" onchange="liveSearch()">
            <option value="">كل الحالات</option>
            <option value="supporter" @selected(request('status') == 'supporter')>مضمون</option>
            <option value="leaning" @selected(request('status') == 'leaning')>يميل</option>
            <option value="undecided" @selected(request('status') == 'undecided')>متردد</option>
            <option value="opposed" @selected(request('status') == 'opposed')>ضد</option>
            <option value="unknown" @selected(request('status') == 'unknown')>غير معروف</option>
        </select>

        {{-- Priority --}}
        <select name="priority" onchange="liveSearch()">
            <option value="">كل الأولويات</option>
            <option value="high" @selected(request('priority') == 'high')>عالي</option>
            <option value="medium" @selected(request('priority') == 'medium')>متوسط</option>
            <option value="low" @selected(request('priority') == 'low')>منخفض</option>
        </select>

        {{-- Delegates --}}
        <select name="delegate_id" onchange="liveSearch()">
            <option value="">كل المندوبين</option>
            @foreach($delegates as $d)
                <option value="{{ $d->id }}" @selected(request('delegate_id') == $d->id)>
                    {{ $d->name }}
                </option>
            @endforeach
        </select>

    </div>

    {{-- ===== ADVANCED FILTERS ===== --}}
    <div class="advanced-filters">

        <div class="filter-title">🔎 فلاتر متقدمة</div>

        <label>
            <input type="checkbox" name="has_notes" onchange="liveSearch()"
                @checked(request('has_notes'))>
            📝 لديه ملاحظات
        </label>

        <label>
            <input type="checkbox" name="needs_action" onchange="liveSearch()"
                @checked(request('needs_action'))>
            🚨 يحتاج إجراء
        </label>

        <label>
            <input type="checkbox" name="high_priority_notes" onchange="liveSearch()"
                @checked(request('high_priority_notes'))>
            🔥 ملاحظات عالية
        </label>

        <label>
            <input type="checkbox" name="has_relationships" onchange="liveSearch()"
                @checked(request('has_relationships'))>
            🔗 لديه علاقات
        </label>

        <label>
            <input type="checkbox" name="has_influencer" onchange="liveSearch()"
                @checked(request('has_influencer'))>
            ⭐ مؤثر أساسي
        </label>

    </div>

</div>

{{-- Success --}}
@if(session('success'))
<div class="success">{{ session('success') }}</div>
@endif

{{-- Bulk Actions --}}

<div class="card" style="margin-bottom:15px;">
    <h2>🔎 بحث سريع وتحديث مباشر</h2>

    <div class="quick-search-box">
        <input type="text"
            id="quick-search-box"
            autocomplete="off"
            placeholder="اكتب الاسم أو رقم الهوية..."
            style="width:100%; padding:10px; margin-bottom:10px;">

        <div id="quick-results" class="quick-results"></div>
    </div>
    <div style="font-size:12px;color:#666;margin-top:6px;display:flex;gap:6px;flex-wrap:wrap;">
        <span>اختصارات:</span>

        <kbd>↑</kbd>
        <kbd>↓</kbd>
        <span>تنقل</span>

        <kbd>Enter</kbd>
        <span>تنفيذ</span>

        <kbd>1</kbd>
        <span>مضمون</span>

        <kbd>2</kbd>
        <span>يميل</span>

        <kbd>3</kbd>
        <span>متردد</span>

        <kbd>Esc</kbd>
        <span>إغلاق</span>
    </div>
</div>
<div id="totals-box" class="totals-grid">

    <div class="total-card">
        <div class="label">إجمالي</div>
        <div class="value">{{ $totals->total ?? 0 }}</div>
    </div>

    <div class="total-card green">
        <div class="label">مضمون</div>
        <div class="value">{{ $totals->supporter ?? 0 }}</div>
    </div>

    <div class="total-card blue">
        <div class="label">يميل</div>
        <div class="value">{{ $totals->leaning ?? 0 }}</div>
    </div>

    <div class="total-card yellow">
        <div class="label">متردد</div>
        <div class="value">{{ $totals->undecided ?? 0 }}</div>
    </div>

    <div class="total-card red">
        <div class="label">ضد</div>
        <div class="value">{{ $totals->opposed ?? 0 }}</div>
    </div>

    <div class="total-card gray">
        <div class="label">غير معروف</div>
        <div class="value">{{ $totals->unknown ?? 0 }}</div>
    </div>

</div>
<div id="bulk-bar" class="bulk-bar hidden">

    <div class="bulk-left">
        <label class="select-all-box">
            <input type="checkbox" id="select-all-visible">
            تحديد الصفحة
        </label>

        <button type="button" id="select-all-filtered" class="bulk-link">
            تحديد كل النتائج
        </button>

        <span class="bulk-count">
            <strong id="selected-count">0</strong> محدد
        </span>
    </div>

    <div class="bulk-right">

        <select id="bulk-delegate">
            <option value="">مندوب</option>
            @foreach($delegates as $d)
                <option value="{{ $d->id }}">{{ $d->name }}</option>
            @endforeach
        </select>

        <button type="button" onclick="bulkAssign()" class="btn btn-primary">
            توزيع
        </button>

        <select id="bulk-status">
            <option value="">الحالة</option>
            <option value="supporter">مضمون</option>
            <option value="leaning">يميل</option>
            <option value="undecided">متردد</option>
            <option value="opposed">ضد</option>
            <option value="unknown">غير معروف</option>
        </select>

        <select id="bulk-priority">
            <option value="">الأولوية</option>
            <option value="high">عالي</option>
            <option value="medium">متوسط</option>
            <option value="low">منخفض</option>
        </select>

        <button type="button" onclick="bulkUpdate()" class="btn btn-success">
            تحديث
        </button>

    </div>
</div>
{{-- Table --}}
<table class="admin-table">
    <thead>
        <tr>
            <th><input type="checkbox" id="select-all"></th>
            <th>الاسم</th>
            <th>المركز</th>
            <th>الحالة</th>
            <th>الأولوية</th>
            <th>المندوب</th>
            <th>مؤشرات</th>
        </tr>
    </thead>

    <tbody id="voters-table">
        @include('operations.data-preparation.partials.table-rows', ['voters' => $voters, 'delegates' => $delegates])
    </tbody>
</table>

{{-- Pagination --}}
<div class="pagination-wrapper">
    <div>
        عرض {{ $voters->firstItem() }} إلى {{ $voters->lastItem() }} من {{ $voters->total() }}
    </div>
    <div>{{ $voters->links() }}</div>
</div>

</div>

{{-- JS --}}
<script>
document.addEventListener('DOMContentLoaded', function () {

    // =========================
    // GLOBAL STATE
    // =========================
    let selectAllFilteredMode = false;
    let timer;
    let quickTimer;
    let currentQuickIndex = -1;

    const quickInput = document.getElementById('quick-search-box');
    const quickResults = document.getElementById('quick-results');

    // =========================
    // INLINE UPDATE
    // =========================
    window.updateVoter = function(element, id, field){

        const value = element.value;
        const container = element.closest('.inline-edit');
        const status = container?.querySelector('.save-status');
        const row = element.closest('tr');

        if(status){
            status.innerHTML = '⏳';
            status.className = 'save-status saving';
        }

        element.disabled = true;

        fetch(`/operations/data-preparation/${id}`, {
            method: 'POST',
            headers: {
                'Content-Type':'application/json',
                'X-CSRF-TOKEN':'{{ csrf_token() }}'
            },
            body: JSON.stringify({ [field]: value })
        })
        .then(res => {
            if(!res.ok) throw new Error();
            return res.json();
        })
        .then(() => {
            if(status){
                status.innerHTML = '✔';
                status.className = 'save-status success';
            }

            if (row) row.style.background = '#ecfdf5';

            setTimeout(()=>{
                if(status) status.innerHTML = '';
                if (row) row.style.background = '';
            }, 1200);
        })
        .catch(() => {
            if(status){
                status.innerHTML = '❌';
                status.className = 'save-status error';
            }
        })
        .finally(()=>{
            element.disabled = false;
        });
    };

    function statusLabel(value) {
        switch (value) {
            case 'supporter': return 'مضمون';
            case 'leaning': return 'يميل';
            case 'undecided': return 'متردد';
            case 'opposed': return 'ضد';
            default: return 'غير معروف';
        }
    }

    function statusClass(value) {
        switch (value) {
            case 'supporter': return 'status-badge status-supporter';
            case 'leaning': return 'status-badge status-leaning';
            case 'undecided': return 'status-badge status-undecided';
            case 'opposed': return 'status-badge status-opposed';
            default: return 'status-badge status-unknown';
        }
    }

    // =========================
    // BULK SYSTEM (GMAIL STYLE)
    // =========================

    function getSelectedIds() {
        if (selectAllFilteredMode) return 'ALL';

        return Array.from(document.querySelectorAll('.row-checkbox:checked'))
            .map(cb => cb.value);
    }

    function updateBulkBar() {
        const selected = document.querySelectorAll('.row-checkbox:checked').length;
        const bar = document.getElementById('bulk-bar');

        if (selectAllFilteredMode) {
            document.getElementById('selected-count').innerText = 'كل النتائج';
            bar.classList.remove('hidden');
            return;
        }

        document.getElementById('selected-count').innerText = selected;

        if (selected > 0) {
            bar.classList.remove('hidden');
        } else {
            bar.classList.add('hidden');
        }
    }

    // select visible
    document.getElementById('select-all-visible')?.addEventListener('change', function () {
        document.querySelectorAll('.row-checkbox').forEach(cb => {
            cb.checked = this.checked;
        });

        selectAllFilteredMode = false;
        updateBulkBar();
    });

    // select ALL FILTERED 🔥
    document.getElementById('select-all-filtered')?.addEventListener('click', function () {
        selectAllFilteredMode = true;

        document.querySelectorAll('.row-checkbox').forEach(cb => {
            cb.checked = true;
        });

        updateBulkBar();
    });

    // checkbox changes
    document.addEventListener('change', function(e){
        if (e.target.classList.contains('row-checkbox')) {
            selectAllFilteredMode = false;
            updateBulkBar();
        }
    });

    // =========================
    // BULK ACTIONS
    // =========================

    window.bulkAssign = function () {

        const ids = getSelectedIds();
        const delegateId = document.getElementById('bulk-delegate').value;

        if (!ids || (Array.isArray(ids) && !ids.length)) {
            return alert('اختر ناخباً');
        }

        if (!delegateId) {
            return alert('اختر مندوب');
        }

        // 🔥 IMPORTANT: collect current filters
        const params = {
            voter_ids: ids,
            assigned_delegate_id: delegateId,

            center_id: document.querySelector('[name="center_id"]')?.value || '',
            status: document.querySelector('[name="status"]')?.value || '',
            priority: document.querySelector('[name="priority"]')?.value || '',
            delegate_id: document.querySelector('[name="delegate_id"]')?.value || '',
            name: document.getElementById('quick-search')?.value || '',
            has_notes: document.querySelector('[name="has_notes"]')?.checked ? 1 : '',
            needs_action: document.querySelector('[name="needs_action"]')?.checked ? 1 : '',
            high_priority_notes: document.querySelector('[name="high_priority_notes"]')?.checked ? 1 : '',
            has_relationships: document.querySelector('[name="has_relationships"]')?.checked ? 1 : '',
            has_influencer: document.querySelector('[name="has_influencer"]')?.checked ? 1 : '',
        };

        fetch("{{ route('operations.data-preparation.bulk-assign') }}", {
            method: 'POST',
            headers: {
                'Content-Type':'application/json',
                'X-CSRF-TOKEN':'{{ csrf_token() }}'
            },
            body: JSON.stringify(params)
        })
        .then(()=> location.reload());
    };

    window.bulkUpdate = function () {

        const ids = getSelectedIds();
        const status = document.getElementById('bulk-status').value;
        const priority = document.getElementById('bulk-priority').value;

        if (!ids || (Array.isArray(ids) && !ids.length)) {
            return alert('اختر ناخباً');
        }

        // 🔥 IMPORTANT: collect current filters
        const params = {
            voter_ids: ids,
            support_status: status,
            priority_level: priority,

            center_id: document.querySelector('[name="center_id"]')?.value || '',
            status: document.querySelector('[name="status"]')?.value || '',
            priority: document.querySelector('[name="priority"]')?.value || '',
            delegate_id: document.querySelector('[name="delegate_id"]')?.value || '',
            name: document.getElementById('quick-search')?.value || '',
            has_notes: document.querySelector('[name="has_notes"]')?.checked ? 1 : '',
            needs_action: document.querySelector('[name="needs_action"]')?.checked ? 1 : '',
            high_priority_notes: document.querySelector('[name="high_priority_notes"]')?.checked ? 1 : '',
            has_relationships: document.querySelector('[name="has_relationships"]')?.checked ? 1 : '',
            has_influencer: document.querySelector('[name="has_influencer"]')?.checked ? 1 : '',
        };

        fetch("{{ route('operations.data-preparation.bulk-status') }}", {
            method: 'POST',
            headers: {
                'Content-Type':'application/json',
                'X-CSRF-TOKEN':'{{ csrf_token() }}'
            },
            body: JSON.stringify(params)
        })
        .then(()=> location.reload());
    };

    // =========================
    // LIVE SEARCH
    // =========================
    window.liveSearch = function() {
        clearTimeout(timer);

        timer = setTimeout(() => {

            const params = new URLSearchParams();

            const name = document.getElementById('quick-search')?.value;
            const center = document.querySelector('[name="center_id"]')?.value;
            const status = document.querySelector('[name="status"]')?.value;
            const priority = document.querySelector('[name="priority"]')?.value;
            const delegateId = document.querySelector('[name="delegate_id"]')?.value;
            const hasNotes = document.querySelector('[name="has_notes"]')?.checked;
            const needsAction = document.querySelector('[name="needs_action"]')?.checked;
            const highPriorityNotes = document.querySelector('[name="high_priority_notes"]')?.checked;
            const hasRelationships = document.querySelector('[name="has_relationships"]')?.checked;
            const hasInfluencer = document.querySelector('[name="has_influencer"]')?.checked;

            if (name) params.append('name', name);
            if (center) params.append('center_id', center);
            if (status) params.append('status', status);
            if (priority) params.append('priority', priority);
            if (delegateId) params.append('delegate_id', delegateId);
            if (hasNotes) params.append('has_notes', 1);
            if (needsAction) params.append('needs_action', 1);
            if (highPriorityNotes) params.append('high_priority_notes', 1);
            if (hasRelationships) params.append('has_relationships', 1);
            if (hasInfluencer) params.append('has_influencer', 1);

            fetch(`/operations/data-preparation/search?${params.toString()}`)
                .then(res => res.json())
                .then(data => {

                    if (data.error) {
                        console.error(data.message);
                        return;
                    }

                    document.getElementById('voters-table').innerHTML = data.html;
                    updateTotals(data.totals);

                    selectAllFilteredMode = false;
                    updateBulkBar();
                })
                .catch(err => {
                    console.error('Search error:', err);
                });

        }, 400);
    };

    // =========================
    // QUICK SEARCH
    // =========================

    function closeQuickResults() {
        quickResults.innerHTML = '';
        quickResults.classList.remove('open');
        currentQuickIndex = -1;
    }

    function activateQuickItem(index) {
        const items = quickResults.querySelectorAll('.quick-item');
        if (!items.length) return;

        items.forEach(item => item.classList.remove('active'));

        if (index < 0) index = 0;
        if (index >= items.length) index = items.length - 1;

        currentQuickIndex = index;
        items[index].classList.add('active');
    }

    function getActiveQuickItem() {
        const items = quickResults.querySelectorAll('.quick-item');
        if (!items.length) return null;

        if (currentQuickIndex === -1) activateQuickItem(0);

        return quickResults.querySelector('.quick-item.active');
    }

    if (quickInput) {
        quickInput.addEventListener('input', function () {
            clearTimeout(quickTimer);

            const query = this.value.trim();
            if (query.length < 2) return closeQuickResults();

            quickResults.innerHTML = '⏳';
            quickResults.classList.add('open');

            quickTimer = setTimeout(() => {
                fetch(`/operations/data-preparation/search?name=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => renderQuickResultsFromHtml(data.html));
            }, 300);
        });

        quickInput.addEventListener('keydown', function (e) {

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activateQuickItem(currentQuickIndex + 1);
            }

            if (e.key === 'ArrowUp') {
                e.preventDefault();
                activateQuickItem(currentQuickIndex - 1);
            }

            if (e.key === 'Enter') {
                e.preventDefault();
                const active = getActiveQuickItem();
                active?.querySelector('button')?.click();
            }

            if (e.key === 'Escape') {
                closeQuickResults();
            }

            const keyMap = {
                '1': 'supporter','١':'supporter',
                '2': 'leaning','٢':'leaning',
                '3': 'undecided','٣':'undecided',
                '4': 'opposed','٤':'opposed',
                '5': 'unknown','٥':'unknown'
            };

            if (keyMap[e.key]) {
                e.preventDefault();

                const active = getActiveQuickItem();
                if (!active) return;

                const id = active.dataset.id;
                const btnIndexMap = {
                    supporter: 1,
                    leaning: 2,
                    undecided: 3,
                    opposed: 4,
                    unknown: 5
                };

                const status = keyMap[e.key];
                const btnIndex = btnIndexMap[status];

                const btn = active.querySelector(`.quick-actions button:nth-child(${btnIndex})`);
                quickUpdate(id, status, { target: btn });
            }
        });
    }

    function renderQuickResultsFromHtml(html) {
        const temp = document.createElement('table');
        temp.innerHTML = html;

        const rows = temp.querySelectorAll('tr');
        let output = '';
        let found = 0;

        rows.forEach((row) => {
            if (found >= 5) return;

            const checkbox = row.querySelector('.row-checkbox');
            if (!checkbox) return;

            const id = checkbox.value;

            const nameCell = row.querySelector('td:nth-child(2)');
            const centerCell = row.querySelector('td:nth-child(3)');
            const statusSelect = row.querySelector('td:nth-child(4) select');

            const voterName = nameCell ? nameCell.innerText.trim() : '';
            const centerName = centerCell ? centerCell.innerText.trim() : '';
            const currentStatus = statusSelect ? statusSelect.value : 'unknown';

            output += `
                <div class="quick-item" data-id="${id}">
                    <div class="quick-item-top">
                        <div class="quick-item-name">${voterName}</div>
                        <div class="${statusClass(currentStatus)}">${statusLabel(currentStatus)}</div>
                    </div>

                    <div class="quick-item-center">${centerName}</div>

                    <div class="quick-actions">
                        <button type="button" onclick="quickUpdate(${id}, 'supporter', event)" class="btn btn-success">مضمون</button>
                        <button type="button" onclick="quickUpdate(${id}, 'leaning', event)" class="btn">يميل</button>
                        <button type="button" onclick="quickUpdate(${id}, 'undecided', event)" class="btn btn-warning">متردد</button>
                        <button type="button" onclick="quickUpdate(${id}, 'opposed', event)" class="btn btn-danger">ضد</button>
                        <button type="button" onclick="quickUpdate(${id}, 'unknown', event)" class="btn">غير معروف</button>
                    </div>
                </div>
            `;

            found++;
        });

        if (!output) {
            output = '<div class="quick-empty">لا توجد نتائج</div>';
        }

        quickResults.innerHTML = output;
        quickResults.classList.add('open');
        currentQuickIndex = -1;
    }
    // =========================
    // QUICK UPDATE
    // =========================
    window.quickUpdate = function(id, status, event = null) {

        const btn = event?.target || null;

        fetch(`/operations/data-preparation/${id}`, {
            method: 'POST',
            headers: {
                'Content-Type':'application/json',
                'X-CSRF-TOKEN':'{{ csrf_token() }}'
            },
            body: JSON.stringify({ support_status: status })
        })
        .then(res => {
            if (!res.ok) throw new Error();
            return res.json();
        })
        .then(() => {

            // ✅ Update badge in UI
            const item = quickResults.querySelector(`.quick-item[data-id="${id}"]`);
            const badge = item?.querySelector('.status-badge');

            if (badge) {
                badge.className = statusClass(status);
                badge.innerText = statusLabel(status);
            }

            // ✅ Button feedback
            if (btn) {
                const original = btn.innerText;

                btn.innerText = '✔';
                btn.style.background = '#16a34a';
                btn.style.color = '#fff';

                setTimeout(() => {
                    btn.innerText = original;
                    btn.style.background = '';
                    btn.style.color = '';
                }, 700);
            }

        })
        .catch(() => {
            if (btn) btn.innerText = '❌';
        });
    };

    function updateTotals(totals) {

        if (!totals) return;

        const box = document.getElementById('totals-box');
        if (!box) return;

        box.innerHTML = `
            <div class="total-card">
                <div class="label">إجمالي</div>
                <div class="value">${totals.total ?? 0}</div>
            </div>

            <div class="total-card green">
                <div class="label">مضمون</div>
                <div class="value">${totals.supporter ?? 0}</div>
            </div>

            <div class="total-card blue">
                <div class="label">يميل</div>
                <div class="value">${totals.leaning ?? 0}</div>
            </div>

            <div class="total-card yellow">
                <div class="label">متردد</div>
                <div class="value">${totals.undecided ?? 0}</div>
            </div>

            <div class="total-card red">
                <div class="label">ضد</div>
                <div class="value">${totals.opposed ?? 0}</div>
            </div>

            <div class="total-card gray">
                <div class="label">غير معروف</div>
                <div class="value">${totals.unknown ?? 0}</div>
            </div>
        `;
    }

});
</script>

{{-- CSS --}}
<style>
.inline-edit{
    display:flex;
    align-items:center;
    gap:6px;
}

.save-status{
    font-size:14px;
    min-width:18px;
}

.save-status.saving{ color:#f59e0b; }
.save-status.success{ color:#16a34a; }
.save-status.error{ color:#dc2626; }

.quick-search-box{
    position:relative;
}
.quick-search-box input{
    margin-bottom: 0 !important;
}
.quick-results{
    display:none;
    position:absolute;        /* 🔥 THIS FIXES EVERYTHING */
    top:100%;
    left:0;
    right:0;
    z-index:999;

    max-height:400px;
    overflow-y:auto;

    border:1px solid #e5e7eb;
    border-radius:12px;
    background:#fff;
    box-shadow:0 15px 40px rgba(0,0,0,0.12);
}
.quick-results{
    backdrop-filter: blur(4px);
}
.quick-results.open{
    display:block;
}

.quick-item{
    padding:12px;
    border-bottom:1px solid #eee;
    cursor:pointer;
}

.quick-item:last-child{
    border-bottom:none;
}

.quick-item.active{
    background:#eff6ff;
    outline: 2px solid #2563eb;
}

.quick-item-top{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:10px;
}

.quick-item-name{
    font-weight:600;
}

.quick-item-center{
    font-size:12px;
    color:#666;
    margin-top:4px;
}

.quick-actions{
    margin-top:8px;
    display:flex;
    gap:6px;
    flex-wrap:wrap;
}

.quick-loading,
.quick-empty,
.quick-error{
    padding:12px;
    color:#666;
}

.quick-error{
    color:#dc2626;
}

.status-badge{
    padding:4px 8px;
    border-radius:999px;
    font-size:12px;
    font-weight:600;
    white-space:nowrap;
}

.status-supporter{
    background:#dcfce7;
    color:#166534;
}

.status-leaning{
    background:#dbeafe;
    color:#1d4ed8;
}

.status-undecided{
    background:#fef3c7;
    color:#92400e;
}

.status-opposed{
    background:#fee2e2;
    color:#991b1b;
}

.status-unknown{
    background:#f3f4f6;
    color:#4b5563;
}
kbd{
    background:#f3f4f6;
    border:1px solid #d1d5db;
    border-bottom:2px solid #9ca3af;
    border-radius:6px;
    padding:2px 6px;
    font-size:11px;
    font-weight:600;
}
.btn-danger{
    background:#dc2626;
    color:#fff;
}

.totals-grid{
    display:grid;
    grid-template-columns: repeat(auto-fit, minmax(120px,1fr));
    gap:10px;
    margin-bottom:15px;
}

.total-card{
    background:#fff;
    padding:12px;
    border-radius:10px;
    text-align:center;
    box-shadow:0 2px 6px rgba(0,0,0,0.05);
}

.total-card .label{
    font-size:12px;
    color:#666;
}

.total-card .value{
    font-size:20px;
    font-weight:bold;
}

.total-card.green{ background:#dcfce7; }
.total-card.blue{ background:#dbeafe; }
.total-card.yellow{ background:#fef3c7; }
.total-card.red{ background:#fee2e2; }
.total-card.gray{ background:#f3f4f6; }

.top-bar{
    display:flex;
    gap:10px;
    align-items:center;
    flex-wrap:wrap;
    background:#fff;
    padding:12px;
    border-radius:12px;
    box-shadow:0 2px 8px rgba(0,0,0,0.05);
    margin-bottom:15px;
}

.top-bar input{
    flex:1;
    min-width:220px;
    padding:10px;
    border-radius:8px;
    border:1px solid #ddd;
}

.top-bar select{
    padding:8px;
    border-radius:8px;
    border:1px solid #ddd;
}
.bulk-bar{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    background:#fff;
    padding:10px 14px;
    border-radius:12px;
    box-shadow:0 2px 8px rgba(0,0,0,0.05);
    margin-bottom:10px;
    flex-wrap:wrap;
}

.bulk-info{
    font-weight:600;
    color:#374151;
}

.bulk-group{
    display:flex;
    gap:6px;
    align-items:center;
}

.bulk-group select{
    padding:6px 8px;
    border-radius:8px;
    border:1px solid #ddd;
    background:#fff;
}

.bulk-bar .btn{
    padding:6px 12px;
    border-radius:8px;
}
.bulk-bar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:10px;
    padding:10px 14px;
    border-radius:12px;
    background:#1f2937;
    color:#fff;
    margin-bottom:10px;
    position:sticky;
    top:10px;
    z-index:50;
    box-shadow:0 6px 20px rgba(0,0,0,0.15);
}

.bulk-bar.hidden{
    display:none;
}

.bulk-left{
    display:flex;
    align-items:center;
    gap:10px;
}

.bulk-right{
    display:flex;
    gap:6px;
    align-items:center;
}

.bulk-link{
    background:none;
    border:none;
    color:#93c5fd;
    cursor:pointer;
    font-weight:600;
}

.bulk-link:hover{
    text-decoration:underline;
}

.bulk-bar select{
    padding:6px 8px;
    border-radius:8px;
    border:none;
}

.bulk-bar .btn{
    padding:6px 12px;
    border-radius:8px;
}

.bulk-count{
    font-size:14px;
}
.bulk-bar select,
.bulk-bar input {
    margin-bottom: 0 !important;
}
.filter-group {
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    width:100%;
}

.advanced-filters {
    width:100%;
    margin-top:10px;
    padding-top:10px;
    border-top:1px solid #eee;

    display:flex;
    gap:10px;
    flex-wrap:wrap;
    align-items:center;
}

.filter-title {
    font-weight:600;
    color:#374151;
    margin-right:10px;
}

.advanced-filters label {
    display:flex;
    align-items:center;
    gap:4px;
    background:#f9fafb;
    padding:5px 10px;
    border-radius:8px;
    font-size:13px;
    cursor:pointer;
}

.advanced-filters label:hover {
    background:#eef2ff;
}
</style>

@endsection
