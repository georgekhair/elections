@if(auth()->user()->hasAnyRole(['delegate','supervisor']))
    @include('layouts.election')
    @php return; @endphp
@endif

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>Election Operations System</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @yield('styles')

    <style>

        .sidebar a {
            display: block;
            padding: 10px 12px;
            border-radius: 10px;
            text-decoration: none;
            color: floralwhite;
            margin-bottom: 4px;
            transition: 0.2s;
        }

        .sidebar a:hover {
            background: #f1f5f9;
        }

        .sidebar a.active {
            background: #2563eb;
            color: #fff;
            font-weight: bold;
        }
    </style>
</head>

<body>

<div class="admin-app wrapper">

    {{-- ================= SIDEBAR ================= --}}
    <div class="sidebar">

        <div class="sidebar-top">

            <h2>نظام الانتخابات</h2>

            {{-- Admin --}}
            @if(auth()->user()->hasRole('admin'))
                <a href="{{ route('admin.dashboard') }}"
                   class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    ⚙️ لوحة التحكم
                </a>
            @endif

            {{-- Operations + Admin --}}
            @if(auth()->user()->hasAnyRole(['admin', 'operations']))
                <a href="{{ route('operations.command-center') }}"
                   class="{{ request()->routeIs('operations.command-center') ? 'active' : '' }}">
                    🎯 غرفة العمليات
                </a>

                <a href="{{ route('operations.map') }}"
                   class="{{ request()->routeIs('operations.map') ? 'active' : '' }}">
                    🗺️ خريطة المراكز
                </a>

                <a href="{{ route('operations.mobilization') }}"
                   class="{{ request()->routeIs('operations.mobilization') ? 'active' : '' }}">
                    📊 تحليل التعبئة
                </a>

                <a href="{{ route('operations.seat-projection.index') }}"
                   class="{{ request()->routeIs('operations.seat-projection.*') ? 'active' : '' }}">
                    🪑 توقع المقاعد
                </a>

                <a href="{{ route('operations.alerts.index') }}"
                   class="{{ request()->routeIs('operations.alerts.*') ? 'active' : '' }}">
                    🚨 التنبيهات
                </a>

                <a href="{{ route('operations.supporters.missing') }}"
                   class="{{ request()->routeIs('operations.supporters.missing') ? 'active' : '' }}">
                    ⚠️ المضمونين لم يصوتوا
                </a>

                <a href="{{ route('operations.tasks.index') }}"
                   class="{{ request()->routeIs('operations.tasks.*') ? 'active' : '' }}">
                    📋 المهام الميدانية
                </a>

                <a href="{{ route('operations.data-validation') }}"
                   class="{{ request()->routeIs('operations.data-validation') ? 'active' : '' }}">
                    📋 جودة البيانات
                </a>
            @endif

            {{-- Data Preparation --}}
            @if(auth()->user()->hasAnyRole(['admin', 'operations', 'data_operator']))
                <a href="{{ route('operations.data-preparation') }}"
                   class="{{ request()->routeIs('operations.data-preparation*') ? 'active' : '' }}">
                    🛠 تجهيز البيانات
                </a>
            @endif

            {{-- Supervisor --}}
            @if(auth()->user()->hasRole('supervisor'))
                <a href="{{ route('supervisor.dashboard') }}"
                   class="{{ request()->routeIs('supervisor.dashboard') ? 'active' : '' }}">
                    🧭 لوحة المشرف
                </a>

                <a href="{{ route('supervisor.voters') }}"
                   class="{{ request()->routeIs('supervisor.voters') ? 'active' : '' }}">
                    🧾 الناخبين
                </a>
            @endif

            {{-- Delegate --}}
            @if(auth()->user()->hasRole('delegate'))
                <a href="{{ route('delegate.dashboard') }}"
                   class="{{ request()->routeIs('delegate.dashboard') ? 'active' : '' }}">
                    📍 لوحة المندوب
                </a>

                <a href="{{ route('delegate.voters') }}"
                   class="{{ request()->routeIs('delegate.voters') ? 'active' : '' }}">
                    🗳️ الناخبين
                </a>
            @endif

            <hr>

            {{-- Field --}}
            @if(auth()->user()->hasAnyRole(['supervisor', 'delegate']))
                <a href="{{ route('field.tasks.inbox') }}"
                   class="{{ request()->routeIs('field.tasks.*') ? 'active' : '' }}">
                    📥 مهامي
                </a>

                <a href="{{ route('field.targets') }}"
                   class="{{ request()->routeIs('field.targets') ? 'active' : '' }}">
                    🎯 قائمة المستهدفين
                </a>
            @endif

            {{-- Admin extra --}}
            @if(auth()->user()->hasRole('admin'))
                <hr>

                <a href="/admin/users"
                   class="{{ request()->is('admin/users*') ? 'active' : '' }}">
                    👥 إدارة المستخدمين
                </a>

                <a href="{{ route('admin.user-families') }}"
                   class="{{ request()->routeIs('admin.user-families*') ? 'active' : '' }}">
                    🏘️ تعيين العائلات
                </a>

                <a href="/admin/polling-centers"
                   class="{{ request()->is('admin/polling-centers*') ? 'active' : '' }}">
                    🏫 المراكز
                </a>

                <a href="/admin/voters"
                   class="{{ request()->is('admin/voters*') ? 'active' : '' }}">
                    🧾 الناخبين
                </a>

                <a href="{{ route('admin.user-hierarchy.index') }}"
                   class="{{ request()->routeIs('admin.user-hierarchy.*') ? 'active' : '' }}">
                    🧬 الهيكل التنظيمي
                </a>

                <a href="{{ route('admin.voters.import') }}"
                   class="{{ request()->routeIs('admin.voters.import*') ? 'active' : '' }}">
                    📥 استيراد الناخبين
                </a>

                <h3 style="margin-top:10px;">الميدان</h3>

                <a href="{{ route('field.targets') }}"
                   class="{{ request()->routeIs('field.targets') ? 'active' : '' }}">
                    🎯 قائمة المستهدفين
                </a>
            @endif

        </div>

        {{-- Logout --}}
        <div class="sidebar-bottom">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="logout-btn">
                    🚪 تسجيل الخروج
                </button>
            </form>
        </div>

    </div>

    {{-- ================= CONTENT ================= --}}
    <div class="content">

        <div class="topbar">

            <h1>{{ auth()->user()->system_title }}</h1>

            <div class="notification-area">
                <span class="notification-icon">🔔</span>
                <span id="task-badge" class="notification-badge" style="display:none;">0</span>
            </div>

            <div class="user-info">
                {{ auth()->user()->name }}
                <small>({{ auth()->user()->getRoleNames()->first() }})</small>
            </div>

        </div>

        <div class="main">
            @yield('content')
        </div>

    </div>

</div>

@yield('scripts')

</body>
</html>
