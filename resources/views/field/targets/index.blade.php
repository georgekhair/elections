@extends('layouts.admin')

@section('content')

<h1>قائمة المستهدفين اليوم</h1>

@if(auth()->user()->hasRole('admin'))

    @include('field.targets.admin')

@else

    @include('field.targets.delegate')

@endif

@endsection
