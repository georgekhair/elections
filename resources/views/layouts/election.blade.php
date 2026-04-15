<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>Election Mode</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">

    @yield('styles')
</head>

<body class="election-layout">

    {{-- 🔥 TOP MINI BAR --}}
    <div class="election-topbar">

        <div class="left">
            {{ auth()->user()->name }}
        </div>

        <div class="center">
            {{ auth()->user()->system_title }}
        </div>

        <div class="right">
            <a href="{{ route('field.tasks.inbox') }}">📋</a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">🚪</button>
            </form>
        </div>

    </div>

    {{-- 🔥 MAIN --}}
    <div class="election-content">
        @yield('content')
    </div>

    @yield('scripts')
<style>
    /* Custom styles for election mode */
    .election-layout {
    background: #f8fafc;
}

.election-topbar {
    position: sticky;
    top: 0;
    z-index: 100;
    background: white;
    padding: 10px 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.election-topbar .center {
    font-weight: bold;
}

.election-topbar .right {
    display: flex;
    gap: 8px;
}

.election-content {
    padding: 10px;
}
    </style>
</body>
</html>
