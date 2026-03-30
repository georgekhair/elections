<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>لوحة المندوب</title>

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
box-shadow:0 1px 3px rgba(0,0,0,0.1);
}

</style>

</head>

<body>

<h2>لوحة المندوب</h2>

<div class="card">
إجمالي الناخبين في المركز: {{ $totalVoters }}
</div>

<div class="card">
تم تسجيل اقتراعهم: {{ $voted }}
</div>

<div class="card">
الناخبين المضمونين: {{ $supporters }}
</div>

<div class="card">
المضمونين الذين صوتوا: {{ $supportersVoted }}
</div>

<a href="{{ route('delegate.voters') }}">
<button>البحث عن ناخب</button>
</a>

</body>
</html>
