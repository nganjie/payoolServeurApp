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
    <div class="table-area">
        <div class="table-wrapper">
            <div class="table-header">
                <h5 class="title">{{ __("All Users") }}</h5>
                <form  class="card-form" action="{{ setRoute('admin.users.maplerad.wallet') }}" method="GET">
                    @csrf
                    <div class="table-btn-area">
                        @include('admin.components.search-input',[
                            'name'  => 'user_search',
                        ])
                         @include('admin.components.button.form-btn',[
                            'type'=>'submit',
                            'text'=>'search'
                         ])
                    </div>
                </form>
                
            </div>
            <div class="table-responsive">
                @include('admin.components.data-table.user-wallet-table',compact('users'))
            </div>
            <nav aria-label="Page navigation example">
                <ul class="pagination">
                  <li class="page-item"><a class="page-link" href="{{ setRoute('admin.users.maplerad.wallet') }}?page={{$meta['page']-1}}">Previous</a></li>
                  <li class="page-item"><a class="page-link" href="{{ setRoute('admin.users.maplerad.wallet') }}?page={{$meta['page']+1}}">{{$meta['page']+1}}</a></li>
                  <li class="page-item"><a class="page-link" href="{{ setRoute('admin.users.maplerad.wallet') }}?page={{$meta['page']+2}}">{{$meta['page']+2}}</a></li>
                  <li class="page-item"><a class="page-link" href="{{ setRoute('admin.users.maplerad.wallet') }}?page={{$meta['page']+1}}">Next</a></li>
                </ul>
              </nav>
        </div>
        
    </div>
@endsection


