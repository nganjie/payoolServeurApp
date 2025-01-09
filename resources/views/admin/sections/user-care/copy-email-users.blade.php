@extends('admin.layouts.master')

@push('css')
@endpush

@section('page-title')
    @include('admin.components.page-title', ['title' => __($page_title)])
@endsection

@section('breadcrumb')
    @include('admin.components.breadcrumb', [
        'breadcrumbs' => [
            [
                'name' => __('Dashboard'),
                'url' => setRoute('admin.dashboard'),
            ],
        ],
        'active' => __('User Care'),
    ])
@endsection

@section('content')
    <div class="custom-card">
        <div class="card-header">
            <h6 class="title">{{ __("Copy mail Users") }}</h6>
        </div>
        <div class="card-body">
            <form class="card-form" action="{{ setRoute('admin.users.copy.email.users') }}" method="post">
                @csrf
                <div class="row mb-10-none">
                    <div class="col-xl-6 col-lg-6 form-group">
                        <label>{{ __("User*") }}</label>
                        <select class="form--control nice-select" name="user_type">
                            <option selected disabled>{{ __("Select Users") }}</option>
                            <option value="all">{{ __("All Users") }}</option>
                            <option value="active">{{ __("Active Users") }}</option>
                            <option value="email_verified">{{ __("Email Unverified") }}</option>
                            <option value="kyc_unverified">{{ __("Kyc Unverified") }}</option>
                            <option value="kyc_verified">{{ __("Kyc verified") }}</option>
                            <option value="kyc_rejected">{{ __("Kyc Rejected") }}</option>
                            <option value="kyc_pending">{{ __("Kyc Pending") }}</option>
                            <option value="banned">{{ __("Banned Users") }}</option>
                        </select>
                    </div>
                    <div class="col-xl-12 col-lg-12 form-group">
                        @include('admin.components.button.form-btn',[
                            'class'         => "w-100 btn-loading",
                            'permission'    => "admin.users.copy.email.users",
                            'text'          => __("Copy Email"),
                        ])
                    </div>
                </div>
            </form>
            <form class="card-form" action="{{ setRoute('admin.users.copy.email.contact') }}" method="post">
                @csrf
                <br><br>
                <div class="col-xl-12 col-lg-12 form-group">
                    @include('admin.components.button.form-btn',[
                        'class'         => "w-100 btn-loading",
                        'permission'    => "admin.users.copy.email.users",
                        'text'          => __("Copy Email Contact Message"),
                    ])
                </div>
            </form>
        </div>
    </div>
@endsection

@push('script')
@endpush
