@extends('layouts.admin')

@section('content')

<h1>لوحة المندوب</h1>

<div class="metrics-grid">

    <div class="card">
        إجمالي الناخبين في المركز<br>
        <b>{{ $totalVoters }}</b>
    </div>

    <div class="card">
        تم تسجيل اقتراعهم<br>
        <b>{{ $voted }}</b>
    </div>

    <div class="card">
        الناخبين المضمونين<br>
        <b>{{ $supporters }}</b>
    </div>

    <div class="card">
        المضمونين الذين صوتوا<br>
        <b>{{ $supportersVoted }}</b>
    </div>

</div>

<div style="margin-top:20px;">
    <a href="{{ route('delegate.voters') }}" class="btn btn-primary">
        🔍 البحث عن ناخب
    </a>
</div>

@endsection
