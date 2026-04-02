@extends('layouts.admin')

@section('content')

<h1>📥 استيراد بيانات الناخبين (كامل)</h1>

<div class="card">
    @if(session('success'))
        <div class="success">{{ session('success') }}</div>
    @endif

    <form method="POST" enctype="multipart/form-data"
          action="{{ route('admin.voters.import.preview') }}">
        @csrf

        <label>ملف CSV / Excel</label>
        <input type="file" name="file" required>
        <div class="mb-3">
            <label class="form-label">نوع الاستيراد</label>
            <select name="import_type" class="form-control" required>
                <option value="safe">آمن - يحافظ على الحالات والتوزيع الحالي</option>
                <option value="full">تحديث كامل - يستبدل البيانات الحالية</option>
                <option value="names_only">تحديث الأسماء فقط</option>
            </select>
        </div>
        <div class="alert alert-warning">
            <strong>تنبيه:</strong>
            خيار "تحديث كامل" سيقوم باستبدال البيانات الحالية مثل الحالة والأولوية والتوزيع إذا كانت موجودة في الملف.
        </div>
        <button class="btn">عرض المعاينة</button>
    </form>
</div>

<div class="card">
    <h2>⚡ تحديث الحالة فقط (سريع وآمن)</h2>
    <p style="color:#666; margin-bottom:10px;">
        هذا الخيار يحدّث فقط حقل الحالة بناءً على رقم الهوية، ولن يعدّل إلا الناخبين الذين حالتهم الحالية غير معروفة.
    </p>

    <form method="POST" enctype="multipart/form-data"
          action="{{ route('admin.voters.import.status.preview') }}">
        @csrf

        <label>ملف CSV / Excel يحتوي national_id و support_status</label>
        <input type="file" name="file" required>


        <button class="btn btn-warning">عرض معاينة تحديث الحالة</button>
    </form>
</div>

<div class="card">
    <h2>آخر عمليات الاستيراد</h2>

    <table class="admin-table">
        <thead>
            <tr>
                <th>الملف</th>
                <th>الحالة</th>
                <th>إجمالي الصفوف</th>
                <th>تم تحديثهم</th>
                <th>تم تجاهلهم</th>
                <th>لم يتم العثور عليهم</th>
                <th>الأخطاء</th>
                <th>إجراء</th>
            </tr>
        </thead>
        <tbody>
            @forelse($runs as $run)
                <tr>
                    <td>{{ $run->original_filename }}</td>
                    <td>{{ $run->status }}</td>
                    <td>{{ $run->total_rows }}</td>
                    <td>{{ $run->updated_rows ?? $run->imported_rows }}</td>
                    <td>{{ $run->skipped_rows }}</td>
                    <td>{{ $run->not_found_rows ?? 0 }}</td>
                    <td>{{ $run->error_rows }}</td>
                    <td>
                        <a class="btn" href="{{ route('admin.voters.import.errors', $run) }}">الأخطاء</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align:center;">لا توجد عمليات استيراد بعد</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
