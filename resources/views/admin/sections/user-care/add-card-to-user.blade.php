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
            <h6 class="title">{{ __("Add Card To User") }}</h6>
        </div>
        <div class="card-body">
            <form class="card-form" action="{{ setRoute('admin.users.add.card.user') }}" method="post">
                @csrf

                <div class="row mb-10-none">
                    <div class="col-xl-6 col-lg-6 form-group">
                        <label>{{ __("User").'*' }}</label>
                        <select class="form--control selectpicker"  id="select-country" data-live-search="true" name="user">
                            @foreach($users as $user)
                            <option data-tokens="{{$user->username}}" value="{{$user->id}}">{{$user->username}}</option>
                            <option data-tokens="china">China</option>
                  <option data-tokens="malayasia">Malayasia</option>
                  <option data-tokens="singapore">Singapore</option>
                            @endforeach

                        </select>
                    </div>
                    <div class="col-xl-6 col-lg-6 form-group">
                        <label>{{ __("Api Type").'*' }}</label>
                        <select class="form--control nice-select" name="api_type">
                            @foreach($apis as $api)
                            <option value="{{$api->id}}">{{$api->name}}</option>
                            @endforeach

                        </select>
                    </div>
                    <div class="col-xl-6 col-lg-6 form-group">
                        @include('admin.components.form.input',[
                            'label'         => __('Card Code').'*',
                            'name'          => 'card_code',
                            'value'         => old('card_code'),
                        ])
                    </div>
                    <div class="col-xl-12 col-lg-12 form-group">
                        @include('admin.components.button.form-btn',[
                            'class'         => "w-100 btn-loading",
                            'permission'    => "admin.users.add.card.user",
                            'text'          => __("Send Card To User"),
                        ])
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('script')
<script>
    $(function() {
 // $('.selectpicker').selectpicker();
});
</script>
@endpush
