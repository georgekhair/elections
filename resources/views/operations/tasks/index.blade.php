@extends('layouts.admin')

@section('content')

<div class="card full-width">

    <div class="table-header">
        <h2>المهام الميدانية</h2>
        <a href="{{ route('operations.tasks.create') }}" class="btn">+ إضافة مهمة</a>
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
                    <th>النوع</th>
                    <th>الأولوية</th>
                    <th>الوصف</th>
                    <th>الوقت</th>
                    <th>المركز</th>
                    <th>المستخدم</th>
                    <th>المصدر</th>
                    <th>الحالة</th>
                    <th>أنشأها</th>
                    <th>إجراء</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tasks as $task)
                    <tr>
                        <td>{{ $task->type }}</td>
                        <td>
                            <span class="badge badge-{{ $task->priority === 'critical' ? 'admin' : ($task->priority === 'high' ? 'operations' : ($task->priority === 'medium' ? 'supervisor' : 'delegate')) }}">
                                {{ $task->priority }}
                            </span>
                        </td>
                        <td>{{ $task->created_at->diffForHumans() }}</td>
                        <td>{{ $task->description }}</td>
                        <td>{{ $task->pollingCenter->name ?? '-' }}</td>
                        <td>{{ $task->user->name ?? '-' }}</td>
                        <td>{{ $task->source }}</td>
                        <td>{{ $task->status }}</td>
                        <td>{{ $task->creator->name ?? 'system' }}</td>
                        <td style="display:flex; gap:8px; flex-wrap:wrap;">
                            @if($task->status === 'pending')
                                <form method="POST" action="{{ route('operations.tasks.progress', $task) }}">
                                    @csrf
                                    <button type="submit" class="btn">بدء</button>
                                </form>
                            @endif

                            @if($task->status !== 'done')
                                <form method="POST" action="{{ route('operations.tasks.done', $task) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-success">إغلاق</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center;padding:20px;">
                            لا توجد مهام حتى الآن
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="pagination" style="margin-top:20px;">
            {{ $tasks->links() }}
        </div>

    </div>
</div>

@endsection
