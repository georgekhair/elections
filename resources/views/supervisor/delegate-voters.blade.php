@extends('layouts.admin')

@section('content')

<h1>ناخبي المندوب: {{ $delegate->name }}</h1>

<div class="card">

    <table class="admin-table">

        <thead>
            <tr>
                <th>الاسم</th>
                <th>المركز</th>
                <th>الحالة</th>
                <th>صوّت</th>
            </tr>
        </thead>

        <tbody>

            @forelse($voters as $voter)

                <tr>

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
