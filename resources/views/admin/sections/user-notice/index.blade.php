

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
    ], 'active' => __("User Notice")])
@endsection

@section('content')
    <div class="custom-card">
        <div class="card-header">
            <h6 class="title">{{ __($page_title) }}</h6>
        </div>
        <div class="card-body">
            <form class="card-form" action="{{ setRoute('admin.notice.limit.notice') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row justify-content-center mb-10-none">

                    <div class="col-xl-12 col-lg-12">
                        <div class="product-tab">
                            <div class="tab-content" id="nav-tabContent">
                                <div class="tab-pane @if (get_default_language_code() == language_const()::NOT_REMOVABLE) fade show active @endif" id="english" role="tabpanel" aria-labelledby="english-tab">
                                    <div class="form-group">
                                        @include('admin.components.form.input',[
                                            'label'     => __("Limit Notice")."*",
                                            'name'      => "limit_notice",
                                            'type'=>'number',
                                            'value'     => old('limit_notice',auth()->user()->limit_notice)
                                        ])
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-12 col-lg-12 form-group">
                        @include('admin.components.button.form-btn',[
                            'class'         => "w-100 btn-loading",
                            'text'          => __("Submit"),
                            'permission'    => "admin.notice.limit.update"
                        ])
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="table-area mt-15">
        <div class="table-wrapper">
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>{{__("Name")}}</th>
                            <th>{{__("Designation")}}</th>
                            <th>{{ __("Rating") }}</th>
                            <th>{{ __("Details") }}</th>
                            <th>{{__("Last Replied")}}</th>
                            <th>{{__("Action")}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($user_notices as $item)
                            <tr data-item="{{ json_encode($item) }}">
                                <td><span class="text--info">
                                  <a href="{{setRoute('admin.users.details', $item->name)}}">  {{ $item->name }}</a></span></td>

                                <td><span class="text--info">{{ $item->designation }}</span></td>
                                <td><span class="text--info">{{ $item->rating }}</span></td>
                                <td><span class="text--info">{{ Str::of($item->details)->limit(20); }}</span></td>
                                <td>{{ $item->created_at->format("Y-m-d H:i A") }}</td>
                                <td>
                                    <button class="btn btn--base edit-modal-button"><a href="notice/update/{{$item->id}}"><i class="las la-pencil-alt"></i></a></button>
                                    <!--<button class="btn btn--base btn--danger delete-modal-button" ><i class="las la-trash-alt"></i></button>-->
                                </td>
                            </tr>
                        @empty
                            @include('admin.components.alerts.empty',['colspan' => 7])
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{--  Item Edit Modal --}}

@endsection

@push('script')

    <script>
    </script>
@endpush
