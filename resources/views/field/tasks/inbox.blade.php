@extends('layouts.admin')

@section('content')

<h1>مهامي الميدانية</h1>

<div class="card full-width">

    <div class="table-header">
        <h2>المهام الحالية</h2>
    </div>

    <div style="padding:20px;">

        <table class="admin-table">
            <thead>
                <tr>
                    <th>النوع</th>
                    <th>المركز</th>
                    <th>الوقت</th>
                    <th>الوصف</th>
                    <th>الأولوية</th>
                    <th>الحالة</th>
                    <th>إجراء</th>
                </tr>
            </thead>

            <tbody>

            @forelse($tasks as $task)

                <tr class="{{ $task->priority === 'critical' ? 'priority-row' : '' }}">

                    <td>{{ $task->type }}</td>

                    <td>{{ $task->pollingCenter->name ?? '-' }}</td>
                    <td>{{ $task->created_at->diffForHumans() }}</td>
                    <td>{{ $task->description }}</td>

                    <td>
                        <span class="badge badge-{{ $task->priority === 'critical' ? 'admin' : ($task->priority === 'high' ? 'operations' : 'supervisor') }}">
                            {{ $task->priority }}
                        </span>
                    </td>

                    <td>{{ $task->status }}</td>

                    <td style="display:flex; gap:8px; flex-wrap:wrap;">

                        @if($task->status === 'pending')
                            <form method="POST" action="{{ route('operations.tasks.progress', $task) }}">
                                @csrf
                                <button class="btn">بدء</button>
                            </form>
                        @endif

                        @if($task->status !== 'done')
                            <form method="POST" action="{{ route('operations.tasks.done', $task) }}">
                                @csrf
                                <button class="btn btn-success">إنهاء</button>
                            </form>
                        @endif

                    </td>

                </tr>

            @empty

                <tr>
                    <td colspan="6" style="text-align:center;padding:20px;">
                        لا توجد مهام حالياً
                    </td>
                </tr>

            @endforelse

            </tbody>

        </table>

    </div>

</div>
<script>
    let lastTaskCount = 0;

async function refreshInbox() {
    try {
        const response = await fetch('{{ route('operations.live.command-center') }}', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        renderInboxTasks(data.tasks);
        // تحديث العداد
        updateTaskBadge(data.user_tasks_count);

        // صوت
        handleTaskAlert(data.user_tasks_count);

        // 🔥 notification popup
        if (data.user_tasks_count > lastTaskCount) {
            showNotification('🔴 لديك مهام جديدة!');
        }

        lastTaskCount = data.user_tasks_count;

    } catch (error) {
        console.error(error);
    }
}

// تشغيل كل 10 ثواني
setInterval(refreshInbox, 10000);

function updateTaskBadge(count) {
    const badge = document.getElementById('task-badge');
    if (!badge) return;

    if (count > 0) {
        badge.style.display = 'inline-block';
        badge.textContent = count;
    } else {
        badge.style.display = 'none';
    }
}
function handleTaskAlert(count) {
    if (count > lastTaskCount) {
        const audio = new Audio('/sounds/alert.mp3');
        audio.play();
    }
}
function showNotification(message) {
    const div = document.createElement('div');

    div.innerHTML = message;

    div.style.position = 'fixed';
    div.style.bottom = '20px';
    div.style.left = '20px';
    div.style.background = '#111';
    div.style.color = '#fff';
    div.style.padding = '12px 16px';
    div.style.borderRadius = '8px';
    div.style.zIndex = 9999;

    document.body.appendChild(div);

    setTimeout(() => div.remove(), 4000);
}
function renderInboxTasks(tasks){
    const tbody = document.querySelector('.admin-table tbody');

    if (!tbody) return;

    tbody.innerHTML = tasks.map(task => `
        <tr>
            <td>${task.type}</td>
            <td>${task.polling_center ?? '-'}</td>
            <td>${task.description}</td>
            <td>${task.priority}</td>
            <td>${task.status}</td>
            <td>-</td>
        </tr>
    `).join('');
}
</script>
@endsection
