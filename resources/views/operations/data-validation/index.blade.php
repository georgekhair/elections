@extends('layouts.admin')

@section('content')

    <h1>لوحة التحقق من جودة البيانات</h1>

    <div class="metrics">

        <div class="metric">
            <div class="metric-title">إجمالي الناخبين</div>
            <div class="metric-value">{{ $metrics['total_voters'] }}</div>
        </div>

        <div class="metric">
            <div class="metric-title">مضمون</div>
            <div class="metric-value">{{ $metrics['supporters'] }}</div>
        </div>

        <div class="metric">
            <div class="metric-title">يميل</div>
            <div class="metric-value">{{ $metrics['leaning'] }}</div>
        </div>

        <div class="metric">
            <div class="metric-title">متردد</div>
            <div class="metric-value">{{ $metrics['undecided'] }}</div>
        </div>

        <div class="metric">
            <div class="metric-title">ضد</div>
            <div class="metric-value">{{ $metrics['opposed'] }}</div>
        </div>

        <div class="metric">
            <div class="metric-title">مسافر</div>
            <div class="metric-value">{{ $metrics['traveling'] }}</div>
        </div>

        <div class="metric">
            <div class="metric-title">غير معروف</div>
            <div class="metric-value">{{ $metrics['unknown'] }}</div>
        </div>

    </div>

    <div class="metrics">

        <div class="metric">
            <div class="metric-title">High Priority</div>
            <div class="metric-value">{{ $metrics['high_priority'] }}</div>
        </div>

        <div class="metric">
            <div class="metric-title">بدون مندوب</div>
            <div class="metric-value">{{ $metrics['without_delegate'] }}</div>
        </div>

        <div class="metric">
            <div class="metric-title">بدون مركز</div>
            <div class="metric-value">{{ $metrics['without_center'] }}</div>
        </div>

        <div class="metric">
            <div class="metric-title">High Priority Unknown</div>
            <div class="metric-value">{{ $metrics['high_priority_unknown'] }}</div>
        </div>

        <div class="metric">
            <div class="metric-title">Target غير موزعين</div>
            <div class="metric-value">{{ $metrics['target_unassigned'] }}</div>
        </div>

    </div>
    <div class="card">
        <h2>إجراءات سريعة لتنظيف البيانات</h2>

        <div class="mobilization-grid">

            {{-- Unknown --}}
            <div class="decision-card warning">
                <div class="decision-header">⚠️ بيانات غير مكتملة</div>
                <div class="decision-value">{{ $metrics['unknown'] }}</div>
                <div class="decision-problem">يوجد ناخبون بدون تصنيف</div>
                <div class="decision-reason">لم يتم تحديد توجههم بعد</div>

                <a class="btn" href="{{ route('operations.data-preparation', ['status' => 'unknown']) }}">
                    فتح ومعالجة
                </a>
            </div>

            {{-- Without Delegate --}}
            <div class="decision-card critical">
                <div class="decision-header">🔥 غير موزعين</div>
                <div class="decision-value">{{ $metrics['without_delegate'] }}</div>
                <div class="decision-problem">ناخبون بدون مندوب</div>
                <div class="decision-reason">لا يوجد متابعة ميدانية لهم</div>

                <a class="btn btn-danger" href="{{ route('operations.data-preparation', ['unassigned' => 1]) }}">
                    توزيع الآن
                </a>
            </div>

            {{-- High Priority Unknown --}}
            <div class="decision-card warning">
                <div class="decision-header">⚠️ High Priority Unknown</div>
                <div class="decision-value">{{ $metrics['high_priority_unknown'] }}</div>
                <div class="decision-problem">ناخبون مهمون غير معروفين</div>
                <div class="decision-reason">High priority لكن بدون تصنيف</div>

                <a class="btn"
                    href="{{ route('operations.data-preparation', ['status' => 'unknown', 'priority' => 'high']) }}">
                    معالجة فورية
                </a>
            </div>

            {{-- Target --}}
            <div class="decision-card target">
                <div class="decision-header">🎯 Target غير موزعين</div>
                <div class="decision-value">{{ $metrics['target_unassigned'] }}</div>
                <div class="decision-problem">ناخبون مهمون بدون متابعة</div>
                <div class="decision-reason">يميل + متردد + مضمون عالي</div>

                <a class="btn btn-danger"
                    href="{{ route('operations.data-preparation', ['unassigned' => 1, 'target' => 1]) }}">
                    معالجة فورية
                </a>
            </div>

        </div>
    </div>
    <div class="card">
        <h2>تنبيهات جودة البيانات</h2>

        <div class="alert-list">
            @forelse($qualityAlerts as $alert)
                <div class="alert-item">
                    <div class="alert-title">{{ $alert['center'] }}</div>
                    <div>{{ $alert['message'] }}</div>
                </div>
            @empty
                <div class="card">
                    لا توجد تنبيهات جودة حالياً
                </div>
            @endforelse
        </div>
    </div>

    <div class="card full-width">
        <div style="padding:20px;">
            <h2>تحليل المراكز</h2>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th>المركز</th>
                        <th>إجمالي</th>
                        <th>مضمون</th>
                        <th>يميل</th>
                        <th>متردد</th>
                        <th>ضد</th>
                        <th>مسافر</th>
                        <th>غير معروف</th>
                        <th>موزعين</th>
                        <th>نسبة التصنيف</th>
                        <th>نسبة التوزيع</th>
                        <th>Readiness</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($centers as $center)
                        <tr>
                            <td>
                                <a href="{{ route('operations.data-preparation', ['center_id' => $center['id']]) }}">
                                    {{ $center['name'] }}
                                </a>
                            </td>
                            <td>{{ $center['voters_count'] }}</td>
                            <td>{{ $center['supporters_count'] }}</td>
                            <td>{{ $center['leaning_count'] }}</td>
                            <td>{{ $center['undecided_count'] }}</td>
                            <td>{{ $center['opposed_count'] }}</td>
                            <td>{{ $center['traveling_count'] }}</td>
                            <td>{{ $center['unknown_count'] }}</td>
                            <td>{{ $center['assigned_count'] }}</td>
                            <td>{{ $center['classification_rate'] }}%</td>
                            <td>{{ $center['assignment_rate'] }}%</td>
                            <td><strong>{{ $center['readiness_score'] }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

@endsection
