@extends('layouts.admin')

@section('content')

<div class="card">

    <h2 class="card-title">تعيين العائلات للمستخدم</h2>

    <form method="POST" action="{{ route('admin.user-families.assign') }}">
        @csrf

        {{-- USER SELECT --}}
        <div class="form-group">
            <label>اختر المستخدم</label>
            <select name="user_id" id="user-select" required>
                <option value="">-- اختر --</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}">
                        {{ $user->name }} ({{ $user->getRoleNames()->first() }})
                    </option>
                @endforeach
            </select>
        </div>

        {{-- CURRENT ASSIGNED --}}
        <div class="form-group">
            <label>العائلات الحالية</label>
            <div id="assigned-families" class="assigned-box">
                اختر مستخدم لعرض العائلات
            </div>
        </div>

        {{-- FAMILY SELECT --}}
        <div class="form-group">
            <label>اختر العائلات</label>
            <input type="text" id="family-search" placeholder="بحث..." class="search-input">

            <div class="family-list">
                @foreach($families as $family)
                    <label class="family-item">
                        <input type="checkbox" name="families[]" value="{{ $family }}">
                        <span>{{ $family }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <button type="submit" class="btn-save">💾 حفظ التعيين</button>

    </form>

</div>

@endsection

@section('styles')
<style>
.card {
    max-width: 800px;
    margin: 0 auto;
    background: #fff;
    padding: 24px;
    border-radius: 16px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.08);
}

.card-title {
    margin-bottom: 20px;
    font-size: 22px;
    font-weight: 800;
}

.form-group {
    margin-bottom: 20px;
}

label {
    display: block;
    margin-bottom: 8px;
    font-weight: 700;
}

select {
    width: 100%;
    padding: 12px;
    border-radius: 10px;
    border: 1px solid #d1d5db;
}

.assigned-box {
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    padding: 12px;
    border-radius: 10px;
    min-height: 50px;
    font-size: 14px;
}

.family-list {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 10px;
}

.family-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 6px;
    border-radius: 8px;
    cursor: pointer;
}

.family-item:hover {
    background: #f1f5f9;
}

.family-item input {
    transform: scale(1.2);
}

.search-input {
    width: 100%;
    padding: 10px;
    margin-bottom: 10px;
    border-radius: 10px;
    border: 1px solid #d1d5db;
}

.btn-save {
    width: 100%;
    padding: 14px;
    border: none;
    background: #16a34a;
    color: #fff;
    font-weight: 800;
    border-radius: 12px;
    cursor: pointer;
}

.btn-save:hover {
    background: #15803d;
}
</style>
@endsection

@section('scripts')
<script>

// 🔍 FAMILY SEARCH
document.getElementById('family-search').addEventListener('input', function() {
    const term = this.value.toLowerCase();

    document.querySelectorAll('.family-item').forEach(item => {
        const text = item.innerText.toLowerCase();
        item.style.display = text.includes(term) ? 'flex' : 'none';
    });
});

// 🔥 LOAD USER FAMILIES
document.getElementById('user-select').addEventListener('change', async function() {

    const userId = this.value;

    if (!userId) return;

    try {
        const userFamiliesUrl = "{{ url('/admin/user-families') }}";

        const res = await fetch(`${userFamiliesUrl}/${userId}`);

        const data = await res.json();

        // display assigned
        const box = document.getElementById('assigned-families');

        if (!data.length) {
            box.innerHTML = 'لا توجد عائلات حالياً';
        } else {
            box.innerHTML = data.map(f => `<span class="tag">${f}</span>`).join('');
        }

        // reset checkboxes
        document.querySelectorAll('.family-item input').forEach(cb => {
            cb.checked = data.includes(cb.value);
        });

    } catch (e) {
        console.error(e);
    }

});
</script>

<style>
.tag {
    display: inline-block;
    background: #e0f2fe;
    color: #0369a1;
    padding: 5px 10px;
    border-radius: 999px;
    margin: 3px;
    font-size: 12px;
}
</style>

@endsection
