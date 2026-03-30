@extends('layouts.admin')

@section('content')

<h1>تحليل التعبئة الانتخابية</h1>

<div class="mobilization-grid">

    <div class="mobilization-card">
        <div>المضمونين الكلي</div>
        <div class="mobilization-value">{{ $supporters }}</div>
    </div>

    <div class="mobilization-card">
        <div>المضمونين الذين صوتوا</div>
        <div class="mobilization-value">{{ $supportersVoted }}</div>
    </div>

    <div class="mobilization-card">
        <div>المضمونين المتبقين</div>
        <div class="mobilization-value">{{ $supportersRemaining }}</div>
    </div>

    <div class="mobilization-card">
        <div>نسبة التعبئة</div>
        <div class="mobilization-value">{{ $supporterTurnout }}%</div>
    </div>

</div>

<div class="card">
    <h2>تحليل التعبئة حسب المركز</h2>

    <table class="admin-table">
        <thead>
            <tr>
                <th>المركز</th>
                <th>المضمونين</th>
                <th>صوتوا</th>
                <th>المتبقي</th>
                <th>نسبة التعبئة</th>
            </tr>
        </thead>
        <tbody>
            @foreach($priorityCenters as $center)
                <tr>
                    <td>{{ $center->name }}</td>
                    <td>{{ $center->supporters }}</td>
                    <td>{{ $center->supporters_voted }}</td>
                    <td>{{ $center->supporters_remaining }}</td>
                    <td>{{ $center->supporter_turnout }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="card">
    <h2>أهم المضمونين الذين لم يصوتوا</h2>

    <table class="admin-table">
        <thead>
            <tr>
                <th>الاسم</th>
                <th>رقم الهوية</th>
                <th>المركز</th>
            </tr>
        </thead>
        <tbody>
            @foreach($topSupporters as $voter)
                <tr>
                    <td>{{ $voter->full_name }}</td>
                    <td>{{ $voter->national_id }}</td>
                    <td>{{ $voter->pollingCenter->name ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@endsection
