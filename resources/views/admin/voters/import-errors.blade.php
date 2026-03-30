@extends('layouts.admin')

@section('content')

<h1>أخطاء الاستيراد</h1>

<div class="card">
    <p><strong>الملف:</strong> {{ $run->original_filename }}</p>
    <p><strong>الحالة:</strong> {{ $run->status }}</p>
    <p><strong>إجمالي الصفوف:</strong> {{ $run->total_rows }}</p>
    <p><strong>تم تحديثهم:</strong> {{ $run->updated_rows ?? $run->imported_rows }}</p>
    <p><strong>تم تجاهلهم:</strong> {{ $run->skipped_rows }}</p>
    <p><strong>تم تجاهلهم لأنهم محدثون مسبقًا:</strong> {{ $run->skipped_already_updated_rows ?? 0 }}</p>
    <p><strong>تم تجاهلهم لأنه لا يوجد تغيير:</strong> {{ $run->skipped_no_change_rows ?? 0 }}</p>
    <p><strong>غير موجودين في النظام:</strong> {{ $run->not_found_rows ?? 0 }}</p>
    <p><strong>الأخطاء:</strong> {{ $run->error_rows }}</p>
</div>

<div class="card full-width">
    <div style="padding:20px;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>رقم السطر</th>
                    <th>نوع الخطأ</th>
                    <th>الرسالة</th>
                    <th>البيانات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($errors as $error)
                    <tr>
                        <td>{{ $error->row_number }}</td>
                        <td>{{ $error->error_type }}</td>
                        <td>{{ $error->message }}</td>
                        <td>
                            <pre style="white-space:pre-wrap;">{{ json_encode($error->row_data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) }}</pre>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align:center;">لا توجد أخطاء</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="pagination" style="margin-top:20px;">
            {{ $errors->links() }}
        </div>
    </div>
</div>

@endsection
