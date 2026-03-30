<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>Election Operations System</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>

<body>

<div class="admin-app wrapper">

    <div class="sidebar">

        <h2>نظام الانتخابات</h2>

        {{-- Admin فقط --}}
        @if(auth()->user()->hasRole('admin'))
            <a href="{{ route('admin.dashboard') }}">لوحة التحكم</a>
        @endif

        {{-- Operations + Admin --}}
        @if(auth()->user()->hasAnyRole(['admin', 'operations']))
            <a href="{{ route('operations.command-center') }}">غرفة العمليات</a>
            <a href="{{ route('operations.map') }}">خريطة المراكز</a>
            <a href="{{ route('operations.mobilization') }}">تحليل التعبئة</a>
            <a href="{{ route('operations.seat-projection.index') }}">توقع المقاعد</a>
            <a href="{{ route('operations.alerts.index') }}">التنبيهات</a>
            <a href="{{ route('operations.supporters.missing') }}">المضمونين لم يصوتوا</a>
            <a href="{{ route('operations.tasks.index') }}">المهام الميدانية</a>
            <a href="{{ route('operations.data-preparation') }}">🛠 تجهيز البيانات</a>
            <a href="{{ route('operations.data-validation') }}">📋 جودة البيانات</a>
        @endif

        {{-- Supervisor --}}
        @if(auth()->user()->hasRole('supervisor'))
            <a href="{{ route('supervisor.dashboard') }}">لوحة مشرف المركز</a>
        @endif

        {{-- Delegate --}}
        @if(auth()->user()->hasRole('delegate'))
            <a href="{{ route('delegate.dashboard') }}">لوحة المندوب</a>
            <a href="{{ route('delegate.voters') }}">الناخبين</a>
        @endif

        <hr>
        @if(auth()->user()->hasAnyRole(['supervisor', 'delegate']))
            <a href="{{ route('field.tasks.inbox') }}">مهامي</a>
        @endif

        {{-- روابط إدارية إضافية - فقط للأدمن لاحقاً --}}
        @if(auth()->user()->hasRole('admin'))
            <a href="/admin/users">المندوبين</a>
            <a href="/admin/polling-centers">المراكز</a>
            <a href="/admin/voters">الناخبين</a>
            <a href="{{ route('admin.voters.import') }}">
                📥 استيراد الناخبين
            </a>


            <h2>الميدان</h2>

        <a href="{{ route('field.targets') }}">
            🎯 قائمة المستهدفين
        </a>
        @endif
        <hr>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="logout-btn">
                تسجيل الخروج
            </button>
        </form>
    </div>

    <div class="content">

        <div class="topbar">
            <div class="topbar-title">
                Election Operations System
            </div>
            <div class="notification-area">

                <span class="notification-icon">🔔</span>

                <span id="task-badge" class="notification-badge" style="display:none;">
                    0
                </span>

            </div>

            <div class="user-info">

            {{ auth()->user()->name }}

            <small>
            (
            {{ auth()->user()->getRoleNames()->first() }}
            )
            </small>

            </div>
        </div>

        <div class="main">
            @yield('content')
        </div>

    </div>

</div>

</body>
</html>
