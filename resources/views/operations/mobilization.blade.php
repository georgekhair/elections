<!DOCTYPE html>
<html lang="ar" dir="rtl">
<meta name="csrf-token" content="{{ csrf_token() }}">
<head>

<meta charset="UTF-8">

<title>تحليل التعبئة الانتخابية</title>

<style>

body{
font-family:Tahoma;
background:#f4f6f9;
padding:30px;
}

table{
width:100%;
border-collapse:collapse;
background:white;
}

th,td{
padding:12px;
border-bottom:1px solid #eee;
text-align:right;
}

th{
background:#f0f0f0;
}

.critical{
background:#ffdddd;
}

.high{
background:#fff0cc;
}

.medium{
background:#e7f4ff;
}

.good{
background:#ddffdd;
}

</style>

</head>

<body>

<h2>تحليل التعبئة الانتخابية</h2>

<table>

<thead>

<tr>
<th>المركز</th>
<th>المضمونين</th>
<th>صوتوا</th>
<th>المتبقي</th>
<th>نسبة التصويت</th>
<th>الأولوية</th>
</tr>

</thead>

<tbody>

@foreach($centers as $center)

<tr class="{{ strtolower($center->priority) }}">

<td>{{ $center->name }}</td>

<td>{{ $center->supporters_total }}</td>

<td>{{ $center->supporters_voted }}</td>

<td>{{ $center->supporters_remaining }}</td>

<td>{{ $center->supporter_turnout }} %</td>

<td>{{ $center->priority }}</td>

</tr>

@endforeach

</tbody>

</table>

</body>

</html>
