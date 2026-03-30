@extends('layouts.admin')

@section('content')

<h1>معاينة الاستيراد</h1>

<div class="card">
    <p><strong>الملف:</strong> {{ $run->original_filename }}</p>
    <p><strong>إجمالي الصفوف:</strong> {{ $summary['total_rows'] }}</p>
    <p><strong>الصفوف الصالحة:</strong> {{ $summary['valid_rows'] }}</p>
    <p><strong>الصفوف التي فيها أخطاء:</strong> {{ $summary['error_rows'] }}</p>

    <form method="POST" action="{{ route('admin.voters.import.confirm', $run) }}">
        @csrf
        <button class="btn">تأكيد الاستيراد</button>
        <a class="btn" href="{{ route('admin.voters.import.errors', $run) }}">عرض الأخطاء</a>
    </form>
</div>

<div class="card full-width">
    <div style="padding:20px;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>رقم السطر</th>
                    <th>البيانات</th>
                    <th>الأخطاء</th>
                </tr>
            </thead>
            <tbody>
                @foreach($preview_rows as $row)
                    <tr>
                        <td>{{ $row['row_number'] }}</td>
                        <td>
                            <pre style="white-space:pre-wrap;">{{ json_encode($row['data'], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) }}</pre>
                        </td>
                        <td>
                            @if($row['errors'])
                                @foreach($row['errors'] as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            @else
                                <span class="badge badge-delegate">صالح</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
