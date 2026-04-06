@extends('layouts.admin')

@section('content')

    <h1>لوحة مشرف المركز</h1>

    <div style="
                    display:inline-block;
                    background:#eef2ff;
                    padding:6px 12px;
                    border-radius:8px;
                    font-size:13px;
                    margin-bottom:12px;
                ">
        📍 {{ $centerName }}
    </div>

    <div class="metrics-grid">

        <div class="metric">
            إجمالي الناخبين<br>
            <b>{{ $totalVoters }}</b>
        </div>

        <div class="metric">
            الذين صوتوا<br>
            <b>{{ $voted }}</b>
        </div>

        <div class="metric">
            المضمونين<br>
            <b>{{ $supporters }}</b>
        </div>

        <div class="metric">
            المضمونين الذين صوتوا<br>
            <b>{{ $supportersVoted }}</b>
        </div>

        <div class="metric">
            المتبقي من المضمونين<br>
            <b>{{ $supportersRemaining }}</b>
        </div>

        <div class="metric">
            نسبة الاقتراع<br>
            <b>{{ $turnout }}%</b>
        </div>

    </div>


    <h2>🏆 ترتيب المندوبين في المركز</h2>

    <div class="card full-width">
        {{-- Desktop Table --}}
        <div class="desktop-only">
            <table class="admin-table">

                <thead>
                    <tr>
                        <th>#</th>
                        <th>المندوب</th>
                        <th>المخصص له</th>
                        <th>صوّتوا</th>
                        <th>الأداء</th>
                        <th>آخر نشاط</th>
                        <th>إجراء</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($leaderboard as $index => $delegate)

                        @php
                            $isTop = $index === 0;
                            $isWeak = $delegate['rate'] < 30;
                        @endphp

                        <tr class="{{ $isTop ? 'priority-high' : ($isWeak ? 'priority-critical' : '') }}">

                            {{-- Ranking --}}
                            <td>
                                <strong>#{{ $index + 1 }}</strong>
                            </td>

                            {{-- Name --}}
                            <td>
                                <a href="{{ route('supervisor.delegate.voters', $delegate['id']) }}">
                                    {{ $delegate['name'] }}
                                </a>

                                @if($isTop)
                                    <span class="badge badge-delegate">الأفضل</span>
                                @endif
                            </td>

                            {{-- Assigned --}}
                            <td>{{ $delegate['assigned'] }}</td>

                            {{-- Votes --}}
                            <td>{{ $delegate['votes'] }}</td>

                            {{-- Performance --}}
                            <td>
                                @if($delegate['rate'] >= 70)
                                    <span class="trend-up">{{ $delegate['rate'] }}%</span>
                                @elseif($delegate['rate'] >= 40)
                                    <span class="trend-stable">{{ $delegate['rate'] }}%</span>
                                @else
                                    <span class="trend-down">{{ $delegate['rate'] }}%</span>
                                @endif
                            </td>

                            {{-- Last Activity --}}
                            <td>
                                @if($delegate['last_activity'])
                                    {{ \Carbon\Carbon::parse($delegate['last_activity'])->diffForHumans() }}
                                @else
                                    <span style="color:#dc2626;">لم يبدأ</span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td>
                                <div class="action-buttons">

                                    <a href="{{ route('supervisor.delegate.voters', $delegate['id']) }}" class="btn btn-view">
                                        عرض
                                    </a>

                                    @if(!empty($delegate['phone']))
                                        <a href="tel:{{ $delegate['phone'] }}" class="btn btn-call">
                                            📞 اتصال
                                        </a>
                                    @endif

                                    <a href="{{ $delegate['whatsapp_summary'] }}" target="_blank" class="btn btn-whatsapp">
                                        <span class="icon">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="white">
                                                <path
                                                    d="M20.52 3.48A11.92 11.92 0 0012.05 0C5.44 0 .05 5.39.05 12c0 2.11.55 4.17 1.6 5.99L0 24l6.2-1.63A11.94 11.94 0 0012.05 24C18.66 24 24.05 18.61 24.05 12c0-3.2-1.25-6.2-3.53-8.52zM12.05 22c-1.85 0-3.67-.49-5.26-1.42l-.38-.23-3.68.97.98-3.59-.25-.37A9.93 9.93 0 012.05 12c0-5.52 4.48-10 10-10s10 4.48 10 10-4.48 10-10 10zm5.49-7.32c-.3-.15-1.78-.88-2.06-.98-.27-.1-.47-.15-.67.15-.2.3-.77.98-.94 1.18-.17.2-.35.22-.65.07-.3-.15-1.28-.47-2.43-1.5-.9-.8-1.5-1.78-1.67-2.08-.17-.3-.02-.46.13-.6.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.07-.15-.67-1.6-.92-2.2-.24-.58-.49-.5-.67-.51l-.57-.01c-.2 0-.52.07-.8.37-.27.3-1.05 1.02-1.05 2.5s1.08 2.9 1.23 3.1c.15.2 2.12 3.24 5.13 4.55.72.31 1.28.5 1.72.64.72.23 1.37.2 1.89.12.58-.09 1.78-.73 2.03-1.44.25-.71.25-1.32.18-1.44-.07-.12-.27-.2-.57-.35z" />
                                            </svg>
                                        </span> تقرير
                                    </a>

                                    <a href="{{ $delegate['whatsapp_alert'] }}" target="_blank" class="btn btn-alert">
                                        <span class="icon">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="white">
                                                <path
                                                    d="M20.52 3.48A11.92 11.92 0 0012.05 0C5.44 0 .05 5.39.05 12c0 2.11.55 4.17 1.6 5.99L0 24l6.2-1.63A11.94 11.94 0 0012.05 24C18.66 24 24.05 18.61 24.05 12c0-3.2-1.25-6.2-3.53-8.52zM12.05 22c-1.85 0-3.67-.49-5.26-1.42l-.38-.23-3.68.97.98-3.59-.25-.37A9.93 9.93 0 012.05 12c0-5.52 4.48-10 10-10s10 4.48 10 10-4.48 10-10 10zm5.49-7.32c-.3-.15-1.78-.88-2.06-.98-.27-.1-.47-.15-.67.15-.2.3-.77.98-.94 1.18-.17.2-.35.22-.65.07-.3-.15-1.28-.47-2.43-1.5-.9-.8-1.5-1.78-1.67-2.08-.17-.3-.02-.46.13-.6.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.07-.15-.67-1.6-.92-2.2-.24-.58-.49-.5-.67-.51l-.57-.01c-.2 0-.52.07-.8.37-.27.3-1.05 1.02-1.05 2.5s1.08 2.9 1.23 3.1c.15.2 2.12 3.24 5.13 4.55.72.31 1.28.5 1.72.64.72.23 1.37.2 1.89.12.58-.09 1.78-.73 2.03-1.44.25-.71.25-1.32.18-1.44-.07-.12-.27-.2-.57-.35z" />
                                            </svg>
                                        </span> تنبيه
                                    </a>

                                </div>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="7">لا يوجد مندوبين في هذا المركز</td>
                        </tr>

                    @endforelse

                </tbody>

            </table>
        </div>
        {{-- Mobile Cards --}}
        <div class="mobile-only">

            @foreach($leaderboard as $index => $delegate)

                <div class="delegate-card">

                    {{-- Header --}}
                    <div class="delegate-top">

                        <div class="delegate-name">
                            #{{ $index + 1 }} {{ $delegate['name'] }}
                        </div>

                        <div class="delegate-rate
                                                    @if($delegate['rate'] >= 70) good
                                                    @elseif($delegate['rate'] >= 40) medium
                                                    @else bad
                                                    @endif
                                                ">
                            {{ $delegate['rate'] }}%
                        </div>

                    </div>

                    {{-- Stats --}}
                    <div class="delegate-stats">
                        <div>👥 {{ $delegate['assigned'] }}</div>
                        <div>🗳 {{ $delegate['votes'] }}</div>
                    </div>

                    {{-- Activity --}}
                    <div class="delegate-activity">
                        @if($delegate['last_activity'])
                            {{ \Carbon\Carbon::parse($delegate['last_activity'])->diffForHumans() }}
                        @else
                            <span class="text-danger">لم يبدأ</span>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="action-buttons mobile">

                        <a href="{{ route('supervisor.delegate.voters', $delegate['id']) }}" class="btn btn-primary">
                            عرض
                        </a>

                        @if(!empty($delegate['phone']))
                            <a href="tel:{{ $delegate['phone'] }}" class="btn btn-success">
                                اتصال
                            </a>
                        @endif

                    </div>

                </div>

            @endforeach

        </div>
    </div>
    <style>
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .action-buttons .btn {
            padding: 6px 10px;
            font-size: 12px;
            border-radius: 6px;
            text-decoration: none;
            color: white;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            /* 👈 THIS is key */
            min-width: 75px;
        }

        /* Colors */
        .btn-view {
            background: #2563eb;
        }

        .btn-call {
            background: #16a34a;
        }

        .btn-whatsapp {
            background: #22c55e;
        }

        .btn-alert {
            background: #f59e0b;
        }

        /* Icon styling */
        .icon {
            font-size: 14px;
            line-height: 1;
        }
    </style>
@endsection
