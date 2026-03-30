@extends('layouts.admin')

@section('content')

<h1>محرك تقدير المقاعد - سانت ليغو</h1>

@if(session('success'))
<div class="alert alert-info">
{{ session('success') }}
</div>
@endif



<div class="metrics">

<div class="metric">
<div class="metric-title">عدد المقاعد</div>
<div class="metric-value">13</div>
</div>

<div class="metric">
<div class="metric-title">نسبة الحسم</div>
<div class="metric-value">5%</div>
</div>

<div class="metric">
<div class="metric-title">الأصوات اللازمة لتجاوز الحسم</div>
<div class="metric-value">{{ round($projection['threshold_votes']) }}</div>
</div>

</div>



<div class="card">

<h2>تحديث تقديرات الأصوات</h2>

<form method="POST" action="{{ route('operations.seat-projection.update') }}">

@csrf

<table class="admin-table">

<thead>

<tr>
<th>القائمة</th>
<th>الأصوات التقديرية</th>
<th>اجتازت الحسم</th>
<th>المقاعد المتوقعة</th>
</tr>

</thead>

<tbody>

@foreach($lists as $list)

<tr class="{{ $list->is_our_list ? 'our-list' : '' }}">

<td>{{ $list->name }}</td>

<td>
<input
type="number"
name="votes[{{ $list->id }}]"
value="{{ $list->estimated_votes }}"
min="0"
style="width:120px;padding:6px"
>
</td>

<td>

{{ array_key_exists($list->name,$projection['qualified']) ? 'نعم' : 'لا' }}

</td>

<td>

{{ $projection['seats'][$list->name] ?? 0 }}

</td>

</tr>

@endforeach

</tbody>

</table>

<br>

<button class="module">
تحديث التقديرات وحساب المقاعد
</button>

</form>

</div>



<div class="card">

<h2>أعلى نواتج القسمة المحتسبة</h2>

<table class="admin-table">

<thead>

<tr>
<th>القائمة</th>
<th>الأصوات</th>
<th>القاسم</th>
<th>ناتج القسمة</th>
</tr>

</thead>

<tbody>

@foreach($projection['top_quotients'] as $row)

<tr>

<td>{{ $row['list'] }}</td>

<td>{{ $row['votes'] }}</td>

<td>{{ $row['divisor'] }}</td>

<td>{{ round($row['quotient'],4) }}</td>

</tr>

@endforeach

</tbody>

</table>

</div>

@endsection
