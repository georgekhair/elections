@extends('layouts.admin')

@section('content')

<h1>لوحة تحكم النظام</h1>

<div class="metrics">

<div class="metric">
<div class="metric-title">الناخبين</div>
<div class="metric-value">{{ $totalVoters }}</div>
</div>

<div class="metric">
<div class="metric-title">صوتوا</div>
<div class="metric-value">{{ $voted }}</div>
</div>

<div class="metric">
<div class="metric-title">المضمونين</div>
<div class="metric-value">{{ $supporters }}</div>
</div>

<div class="metric">
<div class="metric-title">المضمونين الذين صوتوا</div>
<div class="metric-value">{{ $supportersVoted }}</div>
</div>

<div class="metric">
<div class="metric-title">المتبقي</div>
<div class="metric-value">{{ $supportersRemaining }}</div>
</div>

</div>



<div class="card">

<h2>آخر التنبيهات</h2>

@foreach($alerts as $alert)

<div class="alert alert-danger">

<strong>{{ $alert->title }}</strong><br>

{{ $alert->message }}

</div>

@endforeach

</div>
<h2>📡 التواصل مع المندوبين</h2>

@foreach($delegates as $delegate)

<div class="card">

    <strong>{{ $delegate['name'] }}</strong><br>

    📍 {{ $delegate['center'] }}<br>

    📊 {{ $delegate['votes'] }} / {{ $delegate['assigned'] }}
    ({{ $delegate['rate'] }}%)

    <div class="actions">

        <a href="{{ $delegate['whatsapp_summary'] }}" target="_blank" class="btn btn-success">
            📱 تقرير
        </a>

        <a href="{{ $delegate['whatsapp_alert'] }}" target="_blank" class="btn btn-warning">
            ⚠️ تنبيه
        </a>

        <a href="tel:{{ $delegate['phone'] }}" class="btn btn-primary">
            📞 اتصال
        </a>

    </div>

</div>

@endforeach
@endsection
