@extends('layouts.admin')

@section('content')

<h1>قائمة المستهدفين اليوم</h1>

<div class="card">

    <table class="admin-table">

        <thead>
            <tr>
                <th>الاسم</th>
                <th>الحالة</th>
                <th>الأولوية</th>
                <th>الإجراء</th>
            </tr>
        </thead>

        <tbody>

            @foreach($targets as $voter)

                <tr>

                    <td>{{ $voter->full_name }}</td>

                    <td>
                        @if($voter->support_status == 'undecided')
                            🟡 متردد
                        @elseif($voter->support_status == 'leaning')
                            🟠 يميل
                        @elseif($voter->support_status == 'supporter')
                            🟢 مضمون
                        @endif
                    </td>

                    <td>
                        @if($voter->priority_level == 'high')
                            🔴 عالي
                        @elseif($voter->priority_level == 'medium')
                            🟡 متوسط
                        @else
                            🟢 منخفض
                        @endif
                    </td>

                    <td>
                        <button class="btn" onclick="markContacted({{ $voter->id }})">
                            تم التواصل
                        </button>

                        <button class="btn" style="background:#16a34a"
                                onclick="markVoted({{ $voter->id }})">
                            تم التصويت
                        </button>
                    </td>

                </tr>

            @endforeach

        </tbody>

    </table>

</div>

<script>

function markContacted(id){
    fetch(`/field/voters/${id}/contacted`, {
        method:'POST',
        headers:{
            'X-CSRF-TOKEN':'{{ csrf_token() }}'
        }
    });
}

function markVoted(id){
    fetch(`/field/voters/${id}/vote`, {
        method:'POST',
        headers:{
            'X-CSRF-TOKEN':'{{ csrf_token() }}'
        }
    }).then(()=>location.reload());
}

</script>

@endsection
