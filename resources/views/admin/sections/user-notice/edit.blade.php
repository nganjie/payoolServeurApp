@extends('admin.layouts.master')

@push('css')
    <link rel="stylesheet" href="{{ asset('public/backend/css/fontawesome-iconpicker.css') }}">
    <style>
        .fileholder {
            min-height: 374px !important;
        }

        .fileholder-files-view-wrp.accept-single-file .fileholder-single-file-view,.fileholder-files-view-wrp.fileholder-perview-single .fileholder-single-file-view{
            height: 330px !important;
        }
    </style>
@endpush
@section('page-title')
    @include('admin.components.page-title',['title' => __($page_title)])
@endsection

@section('breadcrumb')
    @include('admin.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("admin.dashboard"),
        ]
    ], 'active' => __("Update User Notice")])
@endsection

@section('content')
<div class="custom-card">
    <div class="card-header">
        <h6 class="title">{{ __($page_title) }}</h6>
    </div>
    <div class="card-body">
        <form class="card-form" action="{{ route('admin.notice.'.(isset($user_notice) ? 'update' : 'store')) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" value="{{ isset($user_notice) ? 'PUT' : 'POST' }}">
            @if(isset($user_notice))
            <input type="hidden" name="user_notice_id" value="{{$user_notice->id }}">
            @endif
            <div class="row">

                    <input type="hidden" name="name" value="{{$user_notice->user->username??""}}">

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
                <button type="submit" class="btn--base w-100">{{ __("Update") }}</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('script')
    <script>

    </script>
@endpush
