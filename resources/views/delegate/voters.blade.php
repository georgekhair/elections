<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">

<title>البحث عن ناخب</title>

<style>

body{
font-family:Tahoma;
background:#f5f5f5;
padding:20px;
}

.card{
background:white;
padding:15px;
margin-bottom:10px;
border-radius:10px;
}

.search{
width:100%;
padding:12px;
font-size:18px;
margin-bottom:20px;
}

.btn{
background:red;
color:white;
border:none;
padding:10px 15px;
border-radius:6px;
cursor:pointer;
}

.voted{
color:green;
font-weight:bold;
}

</style>

</head>

<body>

<form method="GET">

<input class="search" type="text" name="search" placeholder="ابحث بالاسم او الهوية">

</form>

@foreach($voters as $voter)

<div class="card">

<h3>{{ $voter->full_name }}</h3>

رقم الهوية: {{ $voter->national_id }}

<br>

الحالة: {{ $voter->support_status_label }}

<br>

@if($voter->is_voted)

<span class="voted">تم الاقتراع</span>

@else

<form method="POST" action="{{ route('delegate.voters.mark',$voter->id) }}">
@csrf

<button class="btn">
اقتراع
</button>

</form>

@endif

</div>

@endforeach

</body>
</html>
