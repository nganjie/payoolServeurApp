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
            <div class="table-btn-area">
                @include('admin.components.search-input',[
                    'name'  => 'user_search',
                ])
            </div>
        </div>
        <div class="card-body">
            <form class="card-form" action="{{ setRoute('admin.users.add.card.user') }}" method="post">
                @csrf

                <div class="row mb-10-none">
                    <div class="col-xl-6 col-lg-6 form-group">
                        <label>{{ __("User").'*' }}</label>
                        <div class="responsive">
                            @include('admin.components.data-table.user-select',compact('users'))
                        </div>
                
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
    itemSearchSelect($("input[name=user_search]"),$(".search_user_select"),"{{ setRoute('admin.users.search-select') }}");
</script>
@endpush
