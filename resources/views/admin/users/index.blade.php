@extends('layouts.admin')

@section('content')

<div class="card full-width">

    <div class="table-header">
        <h2>إدارة المستخدمين</h2>
        <a href="{{ route('admin.users.create') }}" class="btn">+ إضافة مستخدم</a>
    </div>

    @if(session('success'))
        <div class="success" style="margin:15px;">
            {{ session('success') }}
        </div>
    @endif

    <div style="padding: 0 20px 20px 20px;">

        <table class="admin-table">

            <thead>
                <tr>
                    <th>الاسم</th>
                    <th>البريد</th>
                    <th>الدور</th>
                    <th>المركز</th>
                    <th>الحالة</th>
                    <th>إجراء</th>
                </tr>
            </thead>

            <tbody>

                @foreach($users as $user)

                <tr>

                    <td><strong>{{ $user->name }}</strong></td>

                    <td>{{ $user->email }}</td>

                    <td>
                        <span class="badge badge-{{ $user->getRoleNames()->first() }}">
                            {{ $user->getRoleNames()->first() }}
                        </span>
                    </td>

                    <td>{{ $user->pollingCenter->name ?? '-' }}</td>

                    <td>
                        @if($user->is_active)
                            <span class="badge badge-delegate">نشط</span>
                        @else
                            <span class="badge badge-admin">معطل</span>
                        @endif
                    </td>

                    <td>
                        <div class="action-buttons">

                            <a href="{{ route('admin.users.edit',$user) }}" class="btn btn-sm btn-primary">
                                ✏️ تعديل
                            </a>

                            <form method="POST"
                                action="{{ route('admin.users.destroy', $user) }}"
                                onsubmit="return confirm('هل أنت متأكد من حذف هذا المستخدم؟')"
                                style="margin:0;">
                                @csrf
                                @method('DELETE')

                                <button type="submit" class="btn btn-sm btn-danger">
                                    🗑 حذف
                                </button>
                            </form>

                        </div>
                    </td>

                </tr>

                @endforeach

            </tbody>

        </table>

        <div class="pagination" style="margin-top:20px;">
            @if ($users->hasPages())
    <div class="pagination">

        {{-- Previous --}}
        @if ($users->onFirstPage())
            <span class="disabled">‹</span>
        @else
            <a href="{{ $users->previousPageUrl() }}">‹</a>
        @endif

        {{-- Pages --}}
        @foreach ($users->getUrlRange(1, $users->lastPage()) as $page => $url)
            @if ($page == $users->currentPage())
                <span class="active">{{ $page }}</span>
            @else
                <a href="{{ $url }}">{{ $page }}</a>
            @endif
        @endforeach

        {{-- Next --}}
        @if ($users->hasMorePages())
            <a href="{{ $users->nextPageUrl() }}">›</a>
        @else
            <span class="disabled">›</span>
        @endif

    </div>
@endif
        </div>

    </div>

</div>

@endsection
