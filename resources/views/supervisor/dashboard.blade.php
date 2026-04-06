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

                                <a href="{{ route('supervisor.delegate.voters', $delegate['id']) }}" class="btn btn-sm btn-primary">
                                    👁 عرض
                                </a>

                                @if(!empty($delegate['phone']))
                                    <a href="tel:{{ $delegate['phone'] }}" class="btn btn-sm btn-success">
                                        📞 اتصال
                                    </a>
                                @endif

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

@endsection
