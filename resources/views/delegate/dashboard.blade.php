@extends('layouts.admin')

@section('content')

<h1>لوحة المندوب</h1>

<div class="metrics-grid">

    <div class="card">
        إجمالي الناخبين في المركز<br>
        <b>{{ $totalVoters }}</b>
    </div>

    <div class="card">
        تم تسجيل اقتراعهم<br>
        <b>{{ $voted }}</b>
    </div>

    <div class="card">
        الناخبين المضمونين<br>
        <b>{{ $supporters }}</b>
    </div>

    <div class="card">
        المضمونين الذين صوتوا<br>
        <b>{{ $supportersVoted }}</b>
    </div>

</div>

<div style="margin-top:20px;">
    <a href="{{ route('delegate.voters') }}" class="btn btn-primary">
        🔍 البحث عن ناخب
    </a>
</div>

<h2>🔥 يحتاج متابعة الآن</h2>

<div class="priority-list">
@foreach($priorityVoters as $voter)

    <div class="priority-card">
        <div class="name">{{ $voter->full_name }}</div>

        <div class="tags">
            @if($voter->support_status == 'undecided')
                <span class="tag yellow">متردد</span>
            @endif

            @if($voter->support_status == 'leaning')
                <span class="tag blue">يميل</span>
            @endif

            @if($voter->has_issue)
                <span class="tag red">⚠️ مشكلة</span>
            @endif
        </div>

        <button onclick="vote({{ $voter->id }})" class="vote-btn">
            ✔ تم الاقتراع
        </button>
    </div>

@endforeach
</div>
<style>
    body {
    background: #0f172a;
    color: #e5e7eb;
}

/* TITLE */
h1 {
    font-size: 24px;
    margin-bottom: 20px;
}

/* METRICS GRID */
.metrics-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
}

/* CARDS */
.card {
    background: #1f2937;
    padding: 16px;
    border-radius: 12px;
    text-align: center;
    font-size: 14px;
    color: #9ca3af;
}

.card b {
    display: block;
    font-size: 24px;
    color: white;
    margin-top: 6px;
}

/* COLORS PER CARD */
.card:nth-child(1) b { color: #60a5fa; } /* total */
.card:nth-child(2) b { color: #22c55e; } /* voted */
.card:nth-child(3) b { color: #f59e0b; } /* supporters */
.card:nth-child(4) b { color: #10b981; } /* supporters voted */

/* SEARCH BUTTON */
.btn-primary {
    background: #2563eb;
    padding: 12px 18px;
    border-radius: 10px;
    color: white;
    font-weight: bold;
    text-decoration: none;
}

/* PRIORITY SECTION */
.priority-list {
    margin-top: 15px;
}

/* PRIORITY CARD */
.priority-card {
    background: #1f2937;
    padding: 14px;
    border-radius: 10px;
    margin-bottom: 10px;
    border-left: 5px solid #dc2626;
    transition: 0.2s;
}

.priority-card:hover {
    transform: scale(1.01);
}

/* NAME */
.name {
    font-size: 16px;
    font-weight: bold;
    margin-bottom: 6px;
}

/* TAGS */
.tag {
    font-size: 11px;
    padding: 3px 6px;
    border-radius: 6px;
    margin-right: 5px;
}

.tag.yellow { background: #f59e0b; color: black; }
.tag.blue { background: #3b82f6; }
.tag.red { background: #dc2626; }

/* BUTTON */
.vote-btn {
    width: 100%;
    margin-top: 10px;
    padding: 10px;
    font-size: 15px;
    background: #22c55e;
    border: none;
    border-radius: 8px;
    font-weight: bold;
    color: white;
}

/* SECTION TITLE */
h2 {
    margin-top: 25px;
    font-size: 18px;
}
    </style>
@endsection
