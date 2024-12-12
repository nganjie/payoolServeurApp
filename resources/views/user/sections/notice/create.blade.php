@extends('user.layouts.master')

@push('css')

@endpush

@section('breadcrumb')
    @include('user.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("user.dashboard"),
        ]
    ], 'active' => __("User Notice"),
        'link' => __("user.notice.index"),
    ])
@endsection

@section('content')
<div class="body-wrapper">
    <div class="row mb-20-none">
        <div class="col-xl-12 col-lg-12 mb-20">
            <div class="custom-card mt-10">
                <div class="dashboard-header-wrapper">
                    <h4 class="title">{{ __($page_title) }}</h4>
                </div>
                <div class="card-body">
                    <form class="card-form" action="{{ route('user.notice.'.(isset($user_notice) ? 'update' : 'store')) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="_method" value="{{ isset($user_notice) ? 'PUT' : 'POST' }}">
                        @if(isset($user_notice))
                        <input type="hidden" name="user_notice_id" value="{{$user_notice->id }}">
                        @endif
                        <div class="row">

                                <input type="hidden" name="name" value="{{auth()->user()->fullname??""}}">

                            <div class="col-xl-12 col-lg-12 form-group">
                                @php
                                $rating=isset($user_notice) ? $user_notice->rating:'';
                                $details=isset($user_notice) ? $user_notice->details:"";
                                @endphp
                                @include('admin.components.form.input',[
                                    'label'         => __("Rating")."<span>*</span>",
                                    'name'          => "rating",
                                    'type'=>'number',
                                    'value'=>$rating,
                                    'placeholder'   => "Enter Rating 0-to-5...",
                                ])
                            </div>
                            <div class="col-xl-12 col-lg-12 form-group">
                                @include('admin.components.form.textarea',[
                                    'label'         => __("Message")." <span>*</span>",
                                    'name'          => "details",
                                    'value'=>$details,
                                    'placeholder'   => "Write Here…",
                                ])
                            </div>
                        </div>
                        <div class="col-xl-12 col-lg-12">
                            <button type="submit" class="btn--base w-100">{{ __("Add New") }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
    <script>

    </script>
@endpush
