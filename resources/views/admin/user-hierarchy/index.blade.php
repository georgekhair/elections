@extends('layouts.admin')

@section('content')
    <h1>الهيكل التنظيمي الانتخابي</h1>

    @if(session('success'))
        <div class="success">{{ session('success') }}</div>
    @endif

    <div class="card" style="margin-bottom:20px;">
        <h2>المندوبون غير المرتبطين بمشرف</h2>

        @if($unassignedDelegates->count())
            <form method="POST" action="{{ route('admin.user-hierarchy.assign') }}">
                @csrf

                <div style="display:flex; gap:12px; align-items:flex-start; flex-wrap:wrap;">
                    <div style="min-width:300px; flex:1;">
                        @foreach($unassignedDelegates as $delegate)
                            <label style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                                <input type="checkbox" name="delegate_ids[]" value="{{ $delegate->id }}">
                                <span>
                                    {{ $delegate->name }}
                                    <small style="color:#6b7280;">
                                        ({{ $delegate->pollingCenter->name ?? 'بدون مركز' }})
                                    </small>
                                </span>
                            </label>
                        @endforeach
                    </div>

                    <div style="min-width:250px;">
                        <select name="supervisor_id" required style="width:100%; padding:10px;">
                            <option value="">اختر مشرفاً حسب المركز</option>

                            @foreach($groupedSupervisors as $centerName => $group)
                                <optgroup label="📍 {{ $centerName }} ({{ $group->count() }})">
                                    @foreach($group as $s)
                                        <option value="{{ $s->id }}">
                                            🧠 {{ $s->name }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>

                        <button type="submit" class="btn btn-primary" style="margin-top:10px;">
                            ربط المندوبين بالمشرف
                        </button>
                    </div>
                </div>
            </form>
        @else
            <p>لا يوجد مندوبون غير مرتبطين حالياً.</p>
        @endif
    </div>

    <div class="hierarchy-grid">
        @foreach($supervisors as $supervisor)
            <div class="card hierarchy-card">
                <div class="hierarchy-supervisor">
                    <h3 style="margin:0;">
                        🧠 {{ $supervisor->name }}
                    </h3>

                    <div style="font-size:12px; color:#6b7280;">
                        {{ $supervisor->pollingCenter->name ?? 'بدون مركز' }}
                    </div>
                    <div style="font-size:12px; color:#666;">
                        عدد المندوبين: {{ $supervisor->delegates->count() }}
                    </div>
                </div>

                <div class="hierarchy-delegates">
                    @forelse($supervisor->delegates as $delegate)
                                    <form method="POST" action="{{ route('admin.user-hierarchy.assign') }}" class="delegate-row">
                                        @csrf
                                        <input type="hidden" name="delegate_ids[]" value="{{ $delegate->id }}">

                                        <div class="delegate-name">
                                            👤 {{ $delegate->name }}
                                            <div style="font-size:12px; color:#6b7280;">
                                                <span style="
                            background:#eef2ff;
                            padding:2px 6px;
                            border-radius:6px;
                            font-size:11px;
                            margin-left:6px;
                        ">
                                                    {{ $delegate->pollingCenter->name ?? '-' }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="delegate-actions">
                                            <select name="supervisor_id">
                                                <option value="">بدون مشرف</option>

                                                @foreach($groupedSupervisors as $centerName => $group)
                                                    <optgroup label="📍 {{ $centerName }}">
                                                        @foreach($group as $targetSupervisor)
                                                            <option value="{{ $targetSupervisor->id }}"
                                                                @selected($targetSupervisor->id === $supervisor->id)>
                                                                🧠 {{ $targetSupervisor->name }}
                                                            </option>
                                                        @endforeach
                                                    </optgroup>
                                                @endforeach
                                            </select>

                                            <button type="submit" class="btn btn-sm btn-primary">حفظ</button>
                                        </div>
                                    </form>
                    @empty
                        <div style="color:#999;">لا يوجد مندوبون تحت هذا المشرف</div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    <style>
        .hierarchy-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 16px;
        }

        .hierarchy-card {
            padding: 16px;
        }

        .hierarchy-supervisor {
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }

        .delegate-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            padding: 10px 0;
            border-bottom: 1px dashed #eee;
            flex-wrap: wrap;
        }

        .delegate-row:last-child {
            border-bottom: none;
        }

        .delegate-name {
            font-weight: 600;
            flex: 1;
            min-width: 120px;
        }

        .delegate-actions {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        .delegate-actions select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .btn-sm {
            padding: 6px 10px;
            font-size: 13px;
        }
    </style>
@endsection
