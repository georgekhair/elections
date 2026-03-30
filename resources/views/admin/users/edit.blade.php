@extends('layouts.admin')

@section('content')

<div class="card full-width">

<h2>تعديل المستخدم</h2>

<form method="POST" action="{{ route('admin.users.update',$user) }}">
@csrf
@method('PUT')

<label>الاسم</label>
<input type="text" name="name" value="{{ $user->name }}">

<label>البريد</label>
<input type="email" name="email" value="{{ $user->email }}">

<label>كلمة مرور جديدة</label>
<input type="password" name="password">
<div>
    <label>رقم الهاتف</label>
    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}">
</div>
<label>الدور</label>
<select name="role">
@foreach($roles as $role)
<option value="{{ $role }}" {{ $user->hasRole($role) ? 'selected' : '' }}>
{{ $role }}
</option>
@endforeach
</select>

<label>المركز</label>
<select name="polling_center_id">
<option value="">بدون مركز</option>
@foreach($centers as $center)
<option value="{{ $center->id }}" {{ $user->polling_center_id == $center->id ? 'selected' : '' }}>
{{ $center->name }}
</option>
@endforeach
</select>

<label>
<input type="checkbox" name="is_active" value="1" {{ $user->is_active ? 'checked' : '' }}>
نشط
</label>

<br><br>

<button class="btn btn-success">تحديث</button>

</form>

</div>

@endsection
