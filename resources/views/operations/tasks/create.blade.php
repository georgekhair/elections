@extends('layouts.admin')

@section('content')

<div class="card">
    <h2>إضافة مهمة ميدانية</h2>

    @if($errors->any())
        <div class="alert-item" style="margin-bottom:15px;">
            <div class="alert-title">يوجد أخطاء في الإدخال</div>
            <div>
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('operations.tasks.store') }}">
        @csrf

        <label>نوع المهمة</label>
        <select name="type" required>
            <option value="mobilization">mobilization</option>
            <option value="supervisor_call">supervisor_call</option>
            <option value="delegate_followup">delegate_followup</option>
            <option value="transport">transport</option>
            <option value="center_followup">center_followup</option>
        </select>

        <label>الأولوية</label>
        <select name="priority" required>
            <option value="low">low</option>
            <option value="medium">medium</option>
            <option value="high">high</option>
            <option value="critical">critical</option>
        </select>

        <label>الوصف</label>
        <textarea name="description" rows="4" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;margin-bottom:10px;" required></textarea>

        <label>المركز</label>
        <select name="polling_center_id">
            <option value="">بدون مركز محدد</option>
            @foreach($centers as $center)
                <option value="{{ $center->id }}">{{ $center->name }}</option>
            @endforeach
        </select>

        <label>إسناد إلى مستخدم</label>
        <select name="user_id">
            <option value="">بدون إسناد مباشر</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}">
                    {{ $user->name }} ({{ $user->getRoleNames()->first() }})
                </option>
            @endforeach
        </select>

        <button class="btn">حفظ المهمة</button>
    </form>
</div>

@endsection
