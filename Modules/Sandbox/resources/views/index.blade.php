@extends('sandbox::layouts.master')

@section('content')
    <div class="container-fluid">
        <div class="mt-3">
            <h1>Test Sandbox Hub</h1>
        </div>

        @include('sandbox::components.filters')

        @include('sandbox::charts.school-sumary')

        @include('sandbox::charts.chart-details')

        @include('sandbox::innovation')
    </div>

    {{-- chart script --}}
    @include('sandbox::scripts.chart-script')
@endsection
