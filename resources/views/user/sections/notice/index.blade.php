@extends('user.layouts.master')

@push('css')

@endpush

@section('breadcrumb')
    @include('user.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("user.dashboard"),
        ]
    ], 'active' => __("User Notice")])
@endsection

@section('content')
<div class="body-wrapper">
    <div class="table-area mt-10">
        <div class="table-wrapper">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ __("Support Tickets") }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn">
                        <a href="{{ route('user.notice.create') }}" class="btn--base"><i class="las la-plus me-1"></i>{{ __("Add New") }}</a>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>{{__("Designation")}}</th>
                            <th>{{ __("Rating") }}</th>
                            <th>{{ __("Details") }}</th>
                            <th>{{__("Last Replied")}}</th>
                            <th>{{__("Action")}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($user_notices as $item)
                            <tr>
                                <td><span class="text--info">{{ $item->designation }}</span></td>
                                <td><span class="text--info">{{ $item->rating }}</span></td>
                                <td><span class="text--info">{{ Str::of($item->details)->limit(20); }}</span></td>
                                <td>{{ $item->created_at->format("Y-m-d H:i A") }}</td>
                                <td>
                                    <button class="btn btn--base edit-modal-button"><a href="notice/update/{{$item->id}}"><i class="las la-pencil-alt"></i></a></button>
                                </td>
                            </tr>
                        @empty

                            @include('admin.components.alerts.empty',['colspan'=>7])

                        @endforelse

                    </tbody>
                </table>
            </div>
        </div>
        {{ get_paginate($user_notices) }}
    </div>
</div>
@endsection

@push('script')
    <script>

    </script>
@endpush
