@extends('layouts.admin')

@section('content')

<div class="card full-width">

<h2>إضافة مستخدم</h2>

<form method="POST" action="{{ route('admin.users.store') }}">
@csrf

<label>الاسم</label>
<input type="text" name="name" required>

<label>البريد الإلكتروني</label>
<input type="email" name="email" required>

<label>كلمة المرور</label>
<input type="password" name="password" required>
<div>
    <label>رقم الهاتف</label>
    <input type="text" name="phone" value="{{ old('phone') }}">
</div>
<label>الدور</label>
<select name="role">
@foreach($roles as $role)
<option value="{{ $role }}">{{ $role }}</option>
@endforeach
</select>

<label>المركز</label>
<select name="polling_center_id">
<option value="">بدون مركز</option>
@foreach($centers as $center)
<option value="{{ $center->id }}">{{ $center->name }}</option>
@endforeach
</select>

<button class="btn">حفظ المستخدم</button>

</form>

</div>

@endsection
