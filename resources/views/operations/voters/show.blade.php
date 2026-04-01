@extends('layouts.admin')

@section('content')

    <div class="container">
        <div dir="rtl">
            @if(session('success'))
                <div class="alert alert-success mb-3">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger mb-3">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div class="card mb-3 voter-summary-card">
                <div class="card-body">

                    <div class="voter-summary-top">
                        <div>
                            <h3 class="voter-name">{{ $voter->full_name }}</h3>
                            <div class="voter-sub-info">رقم الهوية: {{ $voter->national_id }}</div>
                            <div class="voter-sub-info">رقم الناخب: {{ $voter->voter_no }}</div>
                            <div class="voter-sub-info">الموقع: {{ $voter->location }}</div>
                            <div class="voter-sub-info">المندوب: {{ $voter->assignedDelegate->name ?? 'غير معين' }}</div>
                        </div>

                        <div>
                            @if($voter->is_voted)
                                <span class="info-badge green">تم التصويت</span>
                            @else
                                <span class="info-badge gray">لم يصوّت</span>
                            @endif
                        </div>
                    </div>

                </div>
            </div>

            <div class="mb-3">
                @if(($voter->actionableVoterNotes ?? collect())->where('note_type', 'transportation')->count())
                    <span class="badge bg-danger">🚗 يحتاج مواصلات</span>
                @endif

                @if(($voter->actionableVoterNotes ?? collect())->where('priority', 'high')->count())
                    <span class="badge bg-warning">🔥 أولوية عالية</span>
                @endif
            </div>

            <div class="card mb-3">
                <div class="card-header">الملاحظات</div>

                <div class="card-body">

                    <div class="section-actions">
                        <button type="button" class="btn btn-primary btn-sm" onclick="toggleEditForm('add-note-form')">
                            ➕ إضافة ملاحظة </button>
                    </div>

                    <div id="add-note-form" class="edit-form-box mb-3" style="display:none;">
                        <form method="POST" action="{{ route('voters.notes.store', $voter) }}">
                            @csrf

                            <div class="row">

                                <div class="col-md-3">
                                    <select name="note_type" class="form-control" required>
                                        <option value="general">عامة</option>
                                        <option value="transportation">🚗 مواصلات</option>
                                        <option value="persuasion">🧠 إقناع</option>
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <select name="priority" class="form-control">
                                        <option value="low">منخفضة</option>
                                        <option value="medium">متوسطة</option>
                                        <option value="high">عالية</option>
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <select name="requires_action" class="form-control">
                                        <option value="0">لا يحتاج إجراء</option>
                                        <option value="1">يحتاج إجراء</option>
                                    </select>
                                </div>

                                <div class="col-md-5">
                                    <input type="text" name="content" class="form-control" placeholder="اكتب الملاحظة..."
                                        required>
                                </div>

                            </div>

                            <button class="btn btn-primary mt-2">حفظ الملاحظة</button>
                        </form>
                    </div>
                </div>

                <hr>

                @forelse(($voter->voterNotes ?? collect()) as $note)
                    <div class="record-card">

                        <div class="record-header">
                            <div>
                                <div class="record-title">{{ ucfirst($note->note_type) }}</div>

                                <div class="info-badges">
                                    @if($note->requires_action)
                                        <span class="info-badge red">إجراء</span>
                                    @endif

                                    @if($note->priority)
                                        <span class="info-badge gray">{{ ucfirst($note->priority) }}</span>
                                    @endif
                                </div>
                            </div>

                            <div class="record-actions">
                                <button type="button" class="btn-soft-primary"
                                    onclick="toggleEditForm('note-edit-{{ $note->id }}')">
                                    تعديل
                                </button>

                                <form method="POST" action="{{ route('voters.notes.destroy', $note) }}"
                                    onsubmit="return confirm('Are you sure you want to delete this note?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-soft-danger">
                                        حذف
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="record-content">{{ $note->content }}</div>

                        @if($note->creator)
                            <div class="record-meta">بواسطة: {{ $note->creator->name }}</div>
                        @endif

                        <div id="note-edit-{{ $note->id }}" class="edit-form-box mt-3" style="display:none;">
                            <form method="POST" action="{{ route('voters.notes.update', $note) }}">
                                @csrf
                                @method('PUT')

                                <div class="row g-2">

                                    <div class="col-md-3">
                                        <select name="note_type" class="form-control" required>
                                            <option value="general" @selected($note->note_type === 'general')>عامة</option>
                                            <option value="transportation" @selected($note->note_type === 'transportation')>🚗
                                                مواصلات</option>
                                            <option value="persuasion" @selected($note->note_type === 'persuasion')>🧠 إقناع
                                            </option>
                                        </select>
                                    </div>

                                    <div class="col-md-2">
                                        <select name="priority" class="form-control">
                                            <option value="low" @selected($note->priority === 'low')>منخفض</option>
                                            <option value="medium" @selected($note->priority === 'medium')>متوسط</option>
                                            <option value="high" @selected($note->priority === 'high')>عالي</option>
                                        </select>
                                    </div>

                                    <div class="col-md-2">
                                        <select name="requires_action" class="form-control">
                                            <option value="0" @selected(!$note->requires_action)>لا يحتاج إجراء</option>
                                            <option value="1" @selected($note->requires_action)> يحتاج إجراء</option>
                                        </select>
                                    </div>

                                    <div class="col-md-5">
                                        <input type="text" name="content" class="form-control" value="{{ $note->content }}"
                                            required>
                                    </div>

                                    <div class="col-md-12 mt-2">
                                        <button type="submit" class="btn btn-primary btn-sm">حفظ الملاحظة</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                    </div>
                @empty
                    <div class="text-muted">لا توجد ملاحظات حتى الآن.</div>
                @endforelse

            </div>


            <div class="card">
                <div class="card-header">العلاقات</div>

                <div class="card-body">

                    <button type="button" class="btn btn-success mb-3" onclick="toggleEditForm('add-relationship-form')">
                        + إضافة علاقة
                    </button>

                    <div id="add-relationship-form" class="edit-form-box mb-3" style="display:none;">
                        <form method="POST" action="{{ route('voters.relationships.store', $voter) }}">
                            @csrf

                            <div class="row">

                                <div class="col-md-4 mb-3 position-relative search-wrapper">
                                    <label class="form-label">البحث عن ناخب موجود</label>

                                    <input type="text"
                                        id="voter-search"
                                        class="form-control"
                                        data-target="selected-voter-id"
                                        placeholder="🔎 ابحث بالاسم أو رقم الهوية">

                                    <input type="hidden" name="related_voter_id" id="selected-voter-id">

                                    <div id="search-results" class="search-results"></div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">إذا كان الاسم غير معروف، اكتب اسمًا مؤقتًا</label>

                                    <input type="text" name="related_name" class="form-control"
                                        placeholder="مثال: زوجة أحمد / ابنه / قريب">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">نوع العلاقة</label>

                                    <select name="relationship_type" class="form-control" required>
                                        <option value="spouse">زوج / زوجة</option>
                                        <option value="son">ابن</option>
                                        <option value="daughter">ابنة</option>
                                        <option value="brother">أخ</option>
                                        <option value="sister">أخت</option>
                                        <option value="father">أب</option>
                                        <option value="mother">أم</option>
                                        <option value="relative">قريب</option>
                                        <option value="friend">صديق</option>
                                        <option value="neighbor">جار</option>
                                        <option value="influencer">مؤثر</option>
                                        <option value="other">أخرى</option>
                                    </select>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="form-label">نوع التأثير</label>

                                    <select name="is_primary_influencer" class="form-control" required>
                                        <option value="1">رئيسي</option>
                                        <option value="0" selected>ثانوي</option>
                                    </select>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="form-label">درجة التأثير</label>

                                    <select name="influence_level" class="form-control" required>
                                        <option value="low">منخفضة</option>
                                        <option value="medium" selected>متوسطة</option>
                                        <option value="high">عالية</option>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">ملاحظات</label>

                                    <input type="text" name="notes" class="form-control"
                                        placeholder="مثال: وعد أنهما سيحضران معًا">
                                </div>

                                <div class="col-md-12">
                                    <button class="btn btn-success">حفظ العلاقة</button>
                                </div>

                            </div>
                        </form>
                    </div>
                </div>
                    <hr>

                    @forelse(($voter->relationships ?? collect()) as $rel)
                        <div class="record-card">

                            <div class="record-header">
                                <div>
                                    <div class="record-title">
                                        {{ ucfirst($rel->relationship_type) }}

                                        @if($rel->relatedVoter)
                                            → {{ $rel->relatedVoter->full_name }}
                                        @elseif($rel->related_name)
                                            → {{ $rel->related_name }}
                                        @endif
                                    </div>

                                    <div class="info-badges">
                                        <span class="info-badge gray">{{ ucfirst($rel->influence_level) }}</span>

                                        @if($rel->is_primary_influencer)
                                            <span class="info-badge green">المؤثر الرئيسي</span>
                                        @endif

                                        @if($rel->related_name && !$rel->relatedVoter)
                                            <span class="info-badge yellow">غير مؤكد</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="record-actions">
                                    <button type="button" class="btn-soft-primary"
                                        onclick="toggleEditForm('relationship-edit-{{ $rel->id }}')">
                                        تعديل
                                    </button>

                                    <form method="POST" action="{{ route('voters.relationships.destroy', $rel) }}"
                                        onsubmit="return confirm('هل أنت متأكد أنك تريد حذف هذه العلاقة؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-soft-danger">
                                            حذف
                                        </button>
                                    </form>
                                </div>
                            </div>

                            @if($rel->notes)
                                <div class="record-content">{{ $rel->notes }}</div>
                            @endif

                            <div id="relationship-edit-{{ $rel->id }}" class="edit-form-box mt-3" style="display:none;">
                                <form method="POST" action="{{ route('voters.relationships.update', $rel) }}">
                                    @csrf
                                    @method('PUT')

                                    <div class="row g-2">

                                        <div class="col-md-4">
                                            <div class="col-md-4 position-relative search-wrapper">

                                                <input type="text"
                                                    class="form-control voter-search-edit"
                                                    placeholder="🔎 ابحث عن الناخب"
                                                    value="{{ $rel->relatedVoter->full_name ?? '' }}"
                                                    data-target="edit-voter-id-{{ $rel->id }}">

                                                <input type="hidden"
                                                    name="related_voter_id"
                                                    id="edit-voter-id-{{ $rel->id }}"
                                                    value="{{ $rel->related_voter_id }}">

                                                <div class="search-results"></div>

                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <input type="text" name="related_name" class="form-control"
                                                placeholder="اسم العضو المرتبط مؤقت" value="{{ $rel->related_name }}">
                                        </div>

                                        <div class="col-md-4">
                                            <select name="relationship_type" class="form-control" required>
                                                <option value="spouse" @selected($rel->relationship_type === 'spouse')>زوج
                                                </option>
                                                <option value="son" @selected($rel->relationship_type === 'son')>ابن</option>
                                                <option value="daughter" @selected($rel->relationship_type === 'daughter')>ابنة
                                                </option>
                                                <option value="brother" @selected($rel->relationship_type === 'brother')>أخ
                                                </option>
                                                <option value="sister" @selected($rel->relationship_type === 'sister')>أخت
                                                </option>
                                                <option value="father" @selected($rel->relationship_type === 'father')>أب</option>
                                                <option value="mother" @selected($rel->relationship_type === 'mother')>أم</option>
                                                <option value="relative" @selected($rel->relationship_type === 'relative')>قرابة
                                                </option>
                                                <option value="friend" @selected($rel->relationship_type === 'friend')>صديق
                                                </option>
                                                <option value="neighbor" @selected($rel->relationship_type === 'neighbor')>جارة
                                                </option>
                                                <option value="influencer" @selected($rel->relationship_type === 'influencer')>
                                                    مؤثر</option>
                                                <option value="other" @selected($rel->relationship_type === 'other')>آخر</option>
                                            </select>
                                        </div>

                                        <div class="col-md-3">
                                            <select name="influence_level" class="form-control" required>
                                                <option value="low" @selected($rel->influence_level === 'low')>منخفض</option>
                                                <option value="medium" @selected($rel->influence_level === 'medium')>متوسط
                                                </option>
                                                <option value="high" @selected($rel->influence_level === 'high')>مرتفع</option>
                                            </select>
                                        </div>

                                        <div class="col-md-3">
                                            <select name="is_primary_influencer" class="form-control" required>
                                                <option value="1" @selected($rel->is_primary_influencer)>أولوي</option>
                                                <option value="0" @selected(!$rel->is_primary_influencer)>ثانوي</option>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <input type="text" name="notes" class="form-control" placeholder="الملاحظات"
                                                value="{{ $rel->notes }}">
                                        </div>

                                        <div class="col-md-12 mt-2">
                                            <button type="submit" class="btn btn-primary btn-sm">حفظ العلاقة</button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                        </div>
                    @empty
                        <div class="text-muted">لا توجد علاقات بعد.</div>
                    @endforelse

                </div>
            </div>

        </div>
    </div>
    <script>
        function toggleEditForm(id) {
            const el = document.getElementById(id);
            if (!el) return;

            if (el.style.display === 'none' || el.style.display === '') {
                el.style.display = 'block';
            } else {
                el.style.display = 'none';
            }
        }
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

        let timer;
        const SEARCH_URL = "{{ route('voters.search.simple') }}";

        document.body.addEventListener('input', function (e) {

            if (!e.target.classList.contains('voter-search-edit') && e.target.id !== 'voter-search') return;

            const input = e.target;

            const wrapper = input.closest('.search-wrapper');
            if (!wrapper) return; // ✅ حماية

            const resultsBox = wrapper.querySelector('.search-results');

            const hiddenInputId = input.dataset.target || 'selected-voter-id';
            const hiddenInput = document.getElementById(hiddenInputId);

            clearTimeout(timer);

            const query = input.value.trim();
            hiddenInput.value = '';

            if (query.length < 2) {
                resultsBox.innerHTML = '';
                return;
            }

            timer = setTimeout(() => {

                resultsBox.innerHTML = '<div class="search-item">جاري البحث...</div>';

                fetch(`${SEARCH_URL}?q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {

                        if (!data.length) {
                            resultsBox.innerHTML = '<div class="search-item">لا توجد نتائج</div>';
                            return;
                        }

                        let html = '';

                        data.forEach(voter => {
                            html += `
                                <div class="search-item"
                                    data-id="${voter.id}"
                                    data-name="${voter.full_name}">
                                    ${voter.full_name} (${voter.national_id})
                                </div>
                            `;
                        });

                        resultsBox.innerHTML = html;
                    })
                    .catch(() => {
                        resultsBox.innerHTML = '<div class="search-item">خطأ في البحث</div>';
                    });

            }, 300);
        });

        document.body.addEventListener('click', function (e) {

            const item = e.target.closest('.search-item');

            if (item) {

                const wrapper = item.closest('.search-wrapper');
                if (!wrapper) return; // ✅ حماية

                const input = wrapper.querySelector('input[type="text"]');
                const hiddenInput = wrapper.querySelector('input[type="hidden"]');

                hiddenInput.value = item.dataset.id;
                input.value = item.dataset.name;

                wrapper.querySelector('.search-results').innerHTML = '';
            } else {
                document.querySelectorAll('.search-results').forEach(el => el.innerHTML = '');
            }
        });

    });
    </script>

    <style>
        .container {
            max-width: 1100px;
            margin: 0 auto;
        }

        .card-header {
            font-weight: 700;
            font-size: 16px;
        }

        .voter-summary-card {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.05);
        }

        .voter-summary-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            flex-wrap: wrap;
        }

        .voter-name {
            margin: 0 0 10px;
            font-size: 24px;
            font-weight: 800;
            color: #111827;
        }

        .voter-sub-info {
            color: #4b5563;
            font-size: 14px;
            margin-bottom: 6px;
        }

        .card {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.04);
        }

        .card-body {
            padding: 18px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 14px;
            background: #fff;
        }

        .form-control:focus {
            outline: none;
            border-color: #60a5fa;
            box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.15);
        }

        .btn {
            border-radius: 10px;
            padding: 8px 14px;
            font-weight: 600;
        }

        .text-muted {
            color: #6b7280;
        }

        .section-top-actions {
            margin-bottom: 12px;
        }

        .search-wrapper {
            position: relative;
        }

        .search-results {

            top: calc(100% + 6px);
            right: 0;
            width: 100%;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            max-height: 220px;
            overflow-y: auto;
            z-index: 9999;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }

        .search-item {
            padding: 10px 12px;
            cursor: pointer;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
            text-align: right;
        }

        .search-item:last-child {
            border-bottom: none;
        }

        .search-item:hover {
            background: #f8fafc;
        }

        .edit-form-box {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 14px;
        }

        .record-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 14px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            overflow: visible !important;
        }

        .record-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 10px;
        }

        .record-title {
            font-weight: 700;
            font-size: 15px;
            color: #111827;
        }

        .record-meta {
            color: #6b7280;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .record-content {
            color: #111827;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .record-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-soft-primary,
        .btn-soft-danger {
            border: none;
            border-radius: 8px;
            padding: 6px 10px;
            font-size: 13px;
            cursor: pointer;
        }

        .btn-soft-primary {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .btn-soft-primary:hover {
            background: #bfdbfe;
        }

        .btn-soft-danger {
            background: #fee2e2;
            color: #b91c1c;
        }

        .btn-soft-danger:hover {
            background: #fecaca;
        }

        .info-badges {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin-top: 6px;
        }

        .info-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }

        .info-badge.gray {
            background: #f3f4f6;
            color: #374151;
        }

        .info-badge.red {
            background: #fee2e2;
            color: #b91c1c;
        }

        .info-badge.green {
            background: #dcfce7;
            color: #15803d;
        }

        .info-badge.yellow {
            background: #fef3c7;
            color: #a16207;
        }
        .card-body {
            overflow: visible !important;
        }

        col-md-4.position-relative {
            z-index: 10;
        }
        .card,
        .card-body,
        .edit-form-box {
            overflow: visible !important;
        }
    </style>
@endsection
