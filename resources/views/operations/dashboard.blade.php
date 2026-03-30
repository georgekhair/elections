<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">

<title>غرفة العمليات</title>

<style>

body{
font-family:Tahoma;
background:#f4f6f9;
padding:30px;
}

.card{
background:white;
padding:20px;
margin-bottom:15px;
border-radius:10px;
}

.center{
background:#fff;
padding:15px;
margin-bottom:10px;
border-radius:8px;
}

</style>

</head>

<body>

<h2>غرفة العمليات الانتخابية</h2>

<div class="card">
إجمالي الناخبين: {{ $totalVoters }}
</div>

<div class="card">
الناخبين الذين صوتوا: {{ $voted }}
</div>

<div class="card">
المضمونين: {{ $supporters }}
</div>

<div class="card">
المضمونين الذين صوتوا: {{ $supportersVoted }}
</div>

<div class="card">
المضمونين الذين لم يصوتوا: {{ $supportersRemaining }}
</div>

<h3>المراكز</h3>

@foreach($centers as $center)

<div class="center">

<strong>{{ $center->name }}</strong>

<br>

الناخبين: {{ $center->voters_count }}

<br>

صوتوا: {{ $center->voted_count }}

<br>

المضمونين: {{ $center->supporters }}

<br>

المضمونين الذين صوتوا: {{ $center->supporters_voted }}

<br>

المضمونين المتبقين:
{{ $center->supporters - $center->supporters_voted }}

</div>

@endforeach

</body>

</html>
