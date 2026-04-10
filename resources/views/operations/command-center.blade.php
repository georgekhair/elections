@extends('layouts.admin')

@section('content')

<h1>غرفة العمليات الانتخابية</h1>

<div class="metrics" id="metrics-container">

    <div class="metric">
        <div class="metric-title">الناخبين الكلي</div>
        <div class="metric-value" id="metric-total-voters">{{ $totalVoters }}</div>
    </div>

    <div class="metric">
        <div class="metric-title">صوتوا</div>
        <div class="metric-value" id="metric-voted">{{ $voted }}</div>
    </div>

    <div class="metric">
        <div class="metric-title">المضمونين</div>
        <div class="metric-value" id="metric-supporters">{{ $supporters }}</div>
    </div>

    <div class="metric">
        <div class="metric-title">المضمونين الذين صوتوا</div>
        <div class="metric-value" id="metric-supporters-voted">{{ $supportersVoted }}</div>
    </div>

    <div class="metric">
        <div class="metric-title">المتبقي</div>
        <div class="metric-value" id="metric-supporters-remaining">{{ $supportersRemaining }}</div>
    </div>

</div>

<div class="card" style="margin-bottom: 20px;">
    <strong>آخر تحديث:</strong>
    <span id="live-updated-at">--</span>
</div>
<div class="card">
    <h2>أداء المندوبين</h2>

    <table class="admin-table">
        <thead>
            <tr>
                <th>المندوب</th>
                <th>المركز</th>
                <th>الأداء</th>
                <th>النشاط</th>
                <th>التقييم</th>
                <th>التوصية</th>
            </tr>
        </thead>
        <tbody id="delegates-table-body"></tbody>
    </table>
</div>
<div class="card">
    <h2>ترتيب الأولويات</h2>

    <table class="admin-table">
        <thead>
            <tr>
                <th>المركز</th>
                <th>المضمونين المتبقين</th>
                <th>نسبة التعبئة</th>
                <th>الاتجاه</th>
                <th>درجة الأولوية</th>
                <th>التوصية</th>
            </tr>
        </thead>
        <tbody id="priority-table-body">
            @foreach($centers as $center)
                <tr>
                    <td>{{ $center->name }}</td>
                    <td>{{ $center->supporters_remaining }}</td>
                    <td>{{ $center->supporter_turnout }}%</td>
                    <td>—</td>
                    <td>{{ $center->priority_score ?? '-' }}</td>
                    <td>{{ $center->decision_recommendation ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="card">
    <h2>المهام الميدانية المفتوحة</h2>

    <table class="admin-table">
        <thead>
            <tr>
                <th>النوع</th>
                <th>المركز</th>
                <th>المستخدم</th>
                <th>الأولوية</th>
                <th>الحالة</th>
            </tr>
        </thead>
        <tbody id="tasks-table-body">
            <tr>
                <td colspan="5" style="text-align:center;">جاري التحميل...</td>
            </tr>
        </tbody>
    </table>
</div>
<div class="command-grid">

    <div class="command-panel">

        <h2>التنبيهات اللحظية</h2>

        <div class="alert-list" id="alerts-container">
            @forelse($alerts as $alert)
                <div class="alert-item">
                    <div class="alert-title">{{ $alert->title }}</div>
                    <div>{{ $alert->message }}</div>

                    @if($alert->pollingCenter)
                        <div class="alert-time">
                            المركز: {{ $alert->pollingCenter->name }}
                        </div>
                    @endif
                </div>
            @empty
                <div class="card">
                    لا توجد تنبيهات نشطة حالياً
                </div>
            @endforelse
        </div>

    </div>

    <div class="card">
    <h2>خريطة المراكز</h2>

    <div class="map-wrapper">
        <div id="map"></div>
    </div>
</div>

</div>

<div class="card">

    <h2>تحليل المراكز</h2>

    <table class="admin-table">
        <thead>
            <tr>
                <th>المركز</th>
                <th>المضمونين</th>
                <th>صوتوا</th>
                <th>المتبقي</th>
                <th>نسبة التعبئة</th>
                <th>الاتجاه</th>
            </tr>
        </thead>
        <tbody id="centers-table-body">
        @foreach($centers as $center)
            <tr>
                <td>{{ $center->name }}</td>
                <td>{{ $center->supporters }}</td>
                <td>{{ $center->supporters_voted }}</td>
                <td>{{ $center->supporters_remaining }}</td>
                <td>{{ $center->supporter_turnout }}%</td>
                <td>—</td>
            </tr>
        @endforeach
    </tbody>
    </table>

</div>

<div class="card">

    <h2>توقع المقاعد</h2>

    <table class="admin-table">
        <thead>
            <tr>
                <th>القائمة</th>
                <th>الأصوات التقديرية</th>
                <th>المقاعد</th>
            </tr>
        </thead>
        <tbody id="seat-projection-body">
            @foreach($lists as $list)
                <tr>
                    <td>{{ $list->name }}</td>
                    <td>{{ $list->estimated_votes }}</td>
                    <td>{{ $projection['seats'][$list->name] ?? 0 }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
let map;
let mapMarkers = [];
let lastTaskCount = 0;

function initMap() {
    map = L.map('map').setView([31.7035, 35.2200], 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19
    }).addTo(map);
}


function clearMarkers() {
    mapMarkers.forEach(marker => map.removeLayer(marker));
    mapMarkers = [];
}

function getPriorityColor(priority) {
    if (priority === 'critical') return '#dc2626';
    if (priority === 'high') return '#f97316';
    if (priority === 'medium') return '#eab308';
    return '#16a34a';
}

function renderMap(centers) {
    if (!map) return;
    clearMarkers();

    centers.forEach(center => {
        if (!center.latitude || !center.longitude) return;

        const marker = L.circleMarker([center.latitude, center.longitude], {
            color: getPriorityColor(center.priority_level),
            radius: 12,
            fillOpacity: 0.8
        }).addTo(map);

        marker.bindPopup(
            "<b>" + center.name + "</b><br>" +
            "الناخبين: " + center.voters_count + "<br>" +
            "صوتوا: " + center.voted_count + "<br>" +
            "المضمونين: " + center.supporters + "<br>" +
            "المضمونين الذين صوتوا: " + center.supporters_voted + "<br>" +
            "المتبقي: " + center.supporters_remaining + "<br>" +
            "نسبة التعبئة: " + center.supporter_turnout + "%" + "<br>" +
            "درجة الأولوية: " + center.priority_score + "<br>" +
            "التوصية: " + center.decision_recommendation + "<br>"
        );
        mapMarkers.push(marker);

    });
    setTimeout(() => {
            map.invalidateSize();
        }, 300);
}

function renderMetrics(metrics) {
    document.getElementById('metric-total-voters').textContent = metrics.total_voters;
    document.getElementById('metric-voted').textContent = metrics.voted;
    document.getElementById('metric-supporters').textContent = metrics.supporters;
    document.getElementById('metric-supporters-voted').textContent = metrics.supporters_voted;
    document.getElementById('metric-supporters-remaining').textContent = metrics.supporters_remaining;
}

function renderAlerts(alerts) {
    const container = document.getElementById('alerts-container');

    if (!alerts.length) {
        container.innerHTML = '<div class="card">لا توجد تنبيهات نشطة حالياً</div>';
        return;
    }

    container.innerHTML = alerts.map(alert => `
        <div class="alert-item">
            <div class="alert-title">${alert.title}</div>
            <div>${alert.message}</div>
            ${alert.polling_center ? `<div class="alert-time">المركز: ${alert.polling_center}</div>` : ''}
            ${alert.detected_at ? `<div class="alert-time">وقت التنبيه: ${alert.detected_at}</div>` : ''}
        </div>
    `).join('');
}

function trendLabel(trend, delta) {
    if (trend === 'up') {
        return '⬆️ +' + delta + '%';
    }
    if (trend === 'down') {
        return '⬇️ ' + delta + '%';
    }
    return '➡️ ثابت';
}

function renderCenters(centers) {
    const tbody = document.getElementById('centers-table-body');

    tbody.innerHTML = centers.map(center => `
        <tr>
            <td>${center.name}</td>
            <td>${center.supporters}</td>
            <td>${center.supporters_voted}</td>
            <td>${center.supporters_remaining}</td>
            <td>${center.supporter_turnout}%</td>
            <td>${trendLabel(center.trend, center.trend_delta)}</td>
        </tr>
    `).join('');
}

function renderSeatProjection(rows) {
    const tbody = document.getElementById('seat-projection-body');

    tbody.innerHTML = rows.map(row => `
        <tr>
            <td>${row.name}</td>
            <td>${row.estimated_votes}</td>
            <td>${row.seats}</td>
        </tr>
    `).join('');
}

async function refreshCommandCenter() {
    try {
        const response = await fetch('{{ route('operations.live.command-center') }}', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        renderMetrics(data.metrics);
        renderAlerts(data.alerts);
        renderCenters(data.centers);
        renderSeatProjection(data.seat_projection);
        renderMap(data.centers);
        renderTasks(data.tasks);
        renderPriorityTable(data.centers);
        renderDelegates(data.delegates);
        updateTaskBadge(data.user_tasks_count);

        if (lastTaskCount !== null && data.user_tasks_count > lastTaskCount) {
            showNotification('🔴 لديك مهام جديدة!');
        }
        lastTaskCount = data.user_tasks_count;

        document.getElementById('live-updated-at').textContent = data.updated_at;
    } catch (error) {
        console.error('Live refresh failed:', error);
    }
}
function renderPriorityTable(centers) {
    const tbody = document.getElementById('priority-table-body');

    if (!tbody) return;

    tbody.innerHTML = centers.map(center => `
        <tr>
            <td>${center.name}</td>
            <td>${center.supporters_remaining}</td>
            <td>${center.supporter_turnout}%</td>
            <td>${trendLabel(center.trend, center.trend_delta)}</td>
            <td>${center.priority_score}</td>
            <td>${center.decision_recommendation}</td>
        </tr>
    `).join('');
}
function delegateStatusLabel(status){
    if (status === 'active') return '🟢 نشط';
    if (status === 'idle') return '🟡 خامل';
    return '🔴 متوقف';
}

function renderDelegates(delegates){

    const tbody = document.getElementById('delegates-table-body');

    if (!delegates || !delegates.length) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" style="text-align:center;padding:20px;">
                    لا يوجد بيانات أداء حتى الآن
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = delegates.map(d => `
        <tr>
            <td>${d.name}</td>
            <td>${d.center ?? '-'}</td>
            <td>${d.performance}%</td>
            <td>${delegateStatusLabel(d.status)}</td>
            <td><strong>${d.score}</strong></td>
            <td>${d.recommendation}</td>
        </tr>
    `).join('');
}
function renderTasks(tasks) {
    const tbody = document.getElementById('tasks-table-body');
    if (!tbody) return;

    if (!tasks || !tasks.length) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" style="text-align:center;padding:20px;">
                    لا توجد مهام مفتوحة حالياً
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = tasks.map(task => `
        <tr>
            <td>${task.type}</td>
            <td>${task.polling_center ?? '-'}</td>
            <td>${task.user ?? '-'}</td>
            <td>${task.priority}</td>
            <td>${task.status}</td>
        </tr>
    `).join('');
}
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

initMap();

refreshCommandCenter();
setInterval(refreshCommandCenter, 10000);

</script>

@endsection
