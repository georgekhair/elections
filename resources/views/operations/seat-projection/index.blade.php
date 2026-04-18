@extends('layouts.admin')

@section('content')

    <h1>محرك تقدير المقاعد - سانت ليغو</h1>

    @if(session('success'))
        <div class="alert alert-info">
            {{ session('success') }}
        </div>
    @endif



    <div class="metrics">

        <div class="metric">
            <div class="metric-title">عدد المقاعد</div>
            <div class="metric-value">13</div>
        </div>

        <div class="metric">
            <div class="metric-title">نسبة الحسم</div>
            <div class="metric-value">5%</div>
        </div>

        <div class="metric">
            <div class="metric-title">الأصوات اللازمة لتجاوز الحسم</div>
            <div class="metric-value">{{ round($projection['threshold_votes']) }}</div>
        </div>

    </div>

    <div class="card">

<h2>سيناريو عدد المقترعين</h2>



</div>

    <div class="card">

        <h2>تحديث تقديرات الأصوات</h2>

        <form method="POST" action="{{ route('operations.seat-projection.update') }}">
            <div class="metric">
                <div class="metric-title">مجموع الأصوات المدخلة</div>
                <div class="metric-value">{{ number_format($totalVotes) }}</div>
            </div>
            @csrf

            <table class="admin-table">

                <thead>

                    <tr>
                        <th>القائمة</th>
                        <th>الأصوات التقديرية</th>
                        <th>اجتازت الحسم</th>
                        <th>المقاعد المتوقعة</th>
                        <th>النسبة</th>
                    </tr>

                </thead>

                <tbody>

                    @foreach($lists as $list)

                        <tr class="{{ $list->is_our_list ? 'our-list' : '' }}">

                            <td>{{ $list->name }}</td>

                            <td>
                                <input type="number" name="votes[{{ $list->id }}]" value="{{ $list->estimated_votes }}" min="0"
                                    style="width:120px;padding:6px">
                            </td>

                            <td>

                                {{ array_key_exists($list->name, $projection['qualified']) ? 'نعم' : 'لا' }}

                            </td>

                            <td>

                                {{ $projection['seats'][$list->name] ?? 0 }}

                            </td>
                            <td>
    {{ $totalVotes > 0 ? round(($list->estimated_votes / $totalVotes) * 100, 2) : 0 }}%
</td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

            <br>

            <button class="module">
                تحديث التقديرات وحساب المقاعد
            </button>

        </form>

    </div>

    <div class="card">

<h2>🎯 هدف قائمتنا</h2>

<form method="GET">

    <div style="display:flex;gap:20px;flex-wrap:wrap">

        <div class="metric">
            <div class="metric-title">أصواتنا الحالية</div>
            <div class="metric-value">
                {{ number_format($ourListVotes) }}
            </div>
        </div>

        <div class="metric">
            <div class="metric-title">مقاعدنا الحالية</div>
            <div class="metric-value">
                {{ $ourListSeats }}
            </div>
        </div>

    </div>

    <br>

    <label>كم مقعد نريد؟</label>

    <input type="number"
           name="target_seats"
           value="{{ $targetSeats }}"
           min="1"
           max="13"
           style="padding:8px;width:120px">

    <button class="module">احسب</button>

</form>

@if(!is_null($votesNeeded))
    <div class="alert alert-warning" style="margin-top:15px">

        🔥 للحصول على <strong>{{ $targetSeats }}</strong> مقعد:

        تحتاج تقريباً إلى:

        <strong>{{ number_format($votesNeeded) }}</strong> صوت

        <br><br>

        الزيادة المطلوبة:

        <strong style="color:#dc2626">
            +{{ number_format($votesNeeded - $ourListVotes) }}
        </strong>

    </div>
@endif

</div>

    <div class="card">

        <h2>أعلى نواتج القسمة المحتسبة</h2>

        <table class="admin-table">

            <thead>

                <tr>
                    <th>القائمة</th>
                    <th>الأصوات</th>
                    <th>القاسم</th>
                    <th>ناتج القسمة</th>
                </tr>

            </thead>

            <tbody>

                @foreach($projection['top_quotients'] as $row)

                    <tr class="{{ $list->is_our_list ? 'our-list' : '' }}">

                        <td>{{ $row['list'] }}</td>

                        <td>{{ $row['votes'] }}</td>

                        <td>{{ $row['divisor'] }}</td>

                        <td>{{ round($row['quotient'], 4) }}</td>

                    </tr>

                @endforeach

            </tbody>

        </table>

    </div>
<style>
    .our-list {
    background: #ecfdf5;
    border-right: 4px solid #16a34a;
    font-weight: bold;
}
</style>
@endsection
