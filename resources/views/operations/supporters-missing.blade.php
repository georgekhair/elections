@extends('layouts.admin')

@section('content')

<h1>المضمونين الذين لم يصوتوا</h1>

<div class="card">

<h2>قائمة المضمونين</h2>

<table class="admin-table priority-table">

<thead>

<tr>
<th>الاسم</th>
<th>رقم الهوية</th>
<th>المركز</th>
<th>الموقع</th>
</tr>

</thead>

<tbody>

@foreach($voters as $voter)

<tr>

<td>{{ $voter->full_name }}</td>

<td>{{ $voter->national_id }}</td>

<td>{{ $voter->pollingCenter->name ?? '-' }}</td>

<td>{{ $voter->location }}</td>

</tr>

@endforeach

</tbody>

</table>

</div>

@endsection
