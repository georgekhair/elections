@extends('layouts.admin')

@section('content')

<h1>ناخبي المندوب: {{ $delegate->name }}</h1>

<div class="card">

    <table class="admin-table">

        <thead>
            <tr>
                <th>#</th>
                <th>الاسم</th>
                <th>المركز</th>
                <th>الحالة</th>
                <th>صوّت</th>
                <th>الحالة</th>
            </tr>
        </thead>

        <tbody>

            @forelse($voters as $index => $voter)

                <tr>
                    <td> {{ ($voters->currentPage() - 1) * $voters->perPage() + $index + 1 }} </td>
                    <td>{{ $voter->full_name }}</td>

                    <td>{{ $voter->pollingCenter->name ?? '-' }}</td>

                    <td>
                        {{ $voter->support_status }}
                    </td>

                    <td>
                        @if($voter->is_voted)
                            <span class="badge badge-delegate">✔</span>
                        @else
                            <span class="badge badge-admin">✖</span>
                        @endif
                    </td>
                    <td>
                            @if($voter->is_voted)

                                @if($voter->is_voted)

                                    @if($voter->voted_by_role === 'supervisor' || optional($voter->votedByUser)->isSupervisor())
                                        <span style="color:#f59e0b;">✔ تم بواسطة المشرف</span>
                                    @else
                                        <span style="color:#16a34a;">✔ تم بواسطة المندوب</span>
                                    @endif

                                @endif

                            @else

                                <form method="POST" action="{{ route('supervisor.voters.mark', $voter->id) }}">
                                    @csrf
                                    <button class="btn btn-warning">
                                        تسجيل
                                    </button>
                                </form>

                            @endif

                    </td>

                </tr>

            @empty

                <tr>
                    <td colspan="4">لا يوجد ناخبين</td>
                </tr>

            @endforelse

        </tbody>

    </table>

    {{ $voters->links() }}

</div>

@endsection
