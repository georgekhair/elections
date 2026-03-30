@extends('layouts.admin')

@section('content')

<h1>نظام التنبيهات الانتخابية</h1>

<div class="card">
    <h2>التنبيهات النشطة</h2>

    <div class="alert-list">
        @forelse($alerts as $alert)
            <div class="alert-item">
                <div class="alert-title">{{ $alert->title }}</div>
                <div>{{ $alert->message }}</div>

                @if($alert->pollingCenter)
                    <div class="alert-time">
                        المركز: {{ $alert->pollingCenter->name }}
                    </div>
                @endif

                <div class="alert-time">
                    وقت التنبيه: {{ optional($alert->detected_at)->format('Y-m-d H:i:s') }}
                </div>
            </div>
        @empty
            <div class="card">
                لا توجد تنبيهات حالياً.
            </div>
        @endforelse
    </div>
</div>

<div class="card">
    <h2>إحصائيات التنبيهات حسب المركز</h2>

    <table class="admin-table">
        <thead>
            <tr>
                <th>المركز</th>
                <th>عدد التنبيهات النشطة</th>
            </tr>
        </thead>
        <tbody>
            @foreach($centers as $center)
                <tr>
                    <td>{{ $center->name }}</td>
                    <td>{{ $center->alerts_count ?? 0 }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@endsection
