@extends('admin.layouts.master')

@push('css')

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
    ], 'active' => __("Virtual Card Api")])
@endsection

@section('content')
    <div class="custom-card">
        <div class="card-header">
            <h6 class="title">{{ __("Virtual Card Api") }}</h6>
        </div>
        <div class="card-body">
            <form class="card-form" action="{{ setRoute('admin.virtual.card.api.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method("PUT")
                <div class="row mb-10-none">
                    <div class="col-xl-12   col-lg-12 form-group d-none" style="disolay:none">
                        <label>{{ __("Name") }}*</label>
                        <select class="js-select2 form--control nice-select" name="api_method">
                            <option disabled>{{ __("Select Platfrom") }}</option>
                            <option value="stripe" @if(getCurrentApi() == 'stripe') selected @endif>@lang('Stripe Api')</option>
                            <option value="strowallet" @if(getCurrentApi() == 'strowallet') selected @endif>@lang('Strowallet Api')</option>
                            <option value="sudo" @if(getCurrentApi() == 'sudo') selected @endif>@lang('Sudo Africa')</option>
                            <option value="flutterwave" @if(getCurrentApi() == 'flutterwave') selected @endif>@lang('Flutterwave')</option>
                            <option value="soleaspay" @if(getCurrentApi() == 'soleaspay') selected @endif>@lang('Soleaspay')</option>
                            <option value="eversend" @if(getCurrentApi() == 'eversend') selected @endif>@lang('Eversend')</option>
                        </select>
                    </div>
                    <div class="col-xl-12 col-lg-12 form-group configForm" id="flutterwave">
                        <div class="row" >
                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 form-group">
                                <label>{{ __("Secret Key") }}*</label>
                                <div class="input-group append">
                                    <span class="input-group-text"><i class="las la-key"></i></span>
                                    <input type="text" class="form--control" name="flutterwave_secret_key" value="{{@$existApi&&@$api->config->flutterwave_secret_key }}">
                                </div>
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 form-group">
                                <label>{{ __("Secret Hash") }}*</label>
                                <div class="input-group append">
                                    <span class="input-group-text"><i class="las la-hashtag"></i></span>
                                    <input type="text" class="form--control" name="flutterwave_secret_hash" value="{{ @$api->config->flutterwave_secret_hash }}">
                                </div>
                            </div>
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 form-group">
                                <label>{{ __("Base Url") }}*</label>
                                <div class="input-group append">
                                    <span class="input-group-text"><i class="las la-link"></i></span>
                                    <input type="text" class="form--control" name="flutterwave_url" value="{{ @$api->config->flutterwave_url }}">
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="col-xl-12 col-lg-12 form-group configForm" id="sudo">
                        <div class="row" >
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 form-group">
                                <label>{{ __("Api Key") }}*</label>
                                <div class="input-group append">
                                    <span class="input-group-text"><i class="las la-key"></i></span>
                                    <input type="text" class="form--control" name="sudo_api_key" value="@if(!@$existApi){{ @$api->config->sudo_api_key }} @endif">
                                </div>
                            </div>
                            <div class="col-xl-4 col-lg-4 col-md-4 col-sm-12 form-group">
                                <label>{{ __("Vault ID Url") }}*</label>
                                <div class="input-group append">
                                    <span class="input-group-text"><i class="las la-link"></i></span>
                                    <input type="text" class="form--control" name="sudo_vault_id" value="{{ @$api->config->sudo_vault_id }}">
                                </div>
                            </div>
                            <div class="col-xl-4 col-lg-4 col-md-4 col-sm-12 form-group">
                                <label>{{ __("Base Url") }}*</label>
                                <div class="input-group append">
                                    <span class="input-group-text"><i class="las la-link"></i></span>
                                    <input type="text" class="form--control" name="sudo_url" value="{{ @$api->config->sudo_url }}">
                                </div>
                            </div>
                            <div class="col-xl-4 col-lg-4 col-md-4 col-sm-12 form-group">
                                @include('admin.components.form.switcher', [
                                    'label'         =>__('Mode'),
                                    'value'         => old('sudo_mode',@$api->config->sudo_mode),
                                    'name'          => "sudo_mode",
                                    'options'       => [__('Live') => global_const()::LIVE, __('Sandbox') => global_const()::SANDBOX]
                                ])
                            </div>

                        </div>
                    </div>
                    <div class="col-xl-12 col-lg-12 form-group configForm" id="stripe">
                        <div class="row" >
                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 form-group">
                                <label>{{ __("Public Key") }}*</label>
                                <div class="input-group append">
                                    <span class="input-group-text"><i class="las la-key"></i></span>
                                    <input type="text" class="form--control" name="stripe_public_key" value="{{ @$api->config->stripe_public_key }}">
                                </div>
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 form-group">
                                <label>{{ __("Secret Key") }}*</label>
                                <div class="input-group append">
                                    <span class="input-group-text"><i class="las la-key"></i></span>
                                    <input type="text" class="form--control" name="stripe_secret_key" value="{{ @$api->config->stripe_secret_key }}">
                                </div>
                            </div>
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 form-group">
                                <label>{{ __("Base Url") }}*</label>
                                <div class="input-group append">
                                    <span class="input-group-text"><i class="las la-link"></i></span>
                                    <input type="text" class="form--control" name="stripe_url" value="{{ @$api->config->stripe_url }}">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-12 col-lg-12 form-group configForm" id="strowallet">
                        <div class="row" >
                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 form-group">
                                <label>{{ __("Public Key") }}*</label>
                                <div class="input-group append">
                                    <span class="input-group-text"><i class="las la-key"></i></span>
                                    <input type="text" class="form--control" name="strowallet_public_key" value="@if(@$existApi){{ @$api->config->strowallet_public_key }}@endif">
                                </div>
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 form-group">
                                <label>{{ __("Secret Key") }}*</label>
                                <div class="input-group append">
                                    <span class="input-group-text"><i class="las la-key"></i></span>
                                    <input type="text" class="form--control" name="strowallet_secret_key" value="@if(@$existApi){{ @$api->config->strowallet_secret_key }}@endif">
                                </div>
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 form-group">
                                <label>{{ __("Base Url") }}*</label>
                               <div class="input-group append">
                                    <span class="input-group-text"><i class="las la-link"></i></span>
                                    <input type="text" class="form--control" name="strowallet_url" value="@if(@$existApi){{ @$api->config->strowallet_url }}@endif" 
                                    >
                                </div>
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 form-group">
                                @include('admin.components.form.switcher', [
                                    'label'         => __('Mode'),
                                    'value'         => old('strowallet_mode',@$api->config->strowallet_mode),
                                    'name'          => "strowallet_mode",
                                    'options'       => [__('Live') => global_const()::LIVE,__('Sandbox') => global_const()::SANDBOX]
                                ])
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-12 col-lg-12 form-group configForm" id="soleaspay">
                        <div class="row" >
                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 form-group">
                                <label>{{ __("Public Key") }}*</label>
                                <div class="input-group append">
                                    <span class="input-group-text"><i class="las la-key"></i></span>
                                    <input type="text" class="form--control" name="soleaspay_public_key" value="{{ @$api->config->soleaspay_public_key }}">
                                </div>
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 form-group">
                                <label>{{ __("Secret Key") }}*</label>
                                <div class="input-group append">
                                    <span class="input-group-text"><i class="las la-key"></i></span>
                                    <input type="text" class="form--control" name="soleaspay_secret_key" value="{{ @$api->config->soleaspay_secret_key }}">
                                </div>
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 form-group">
                                <label>{{ __("Base Url") }}*</label>
                                <div class="input-group append">
                                    <span class="input-group-text"><i class="las la-link"></i></span>
                                    <input type="text" class="form--control" name="soleaspay_url" value="{{ @$api->config->soleaspay_url }}">
                                </div>
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 form-group">
                                @include('admin.components.form.switcher', [
                                    'label'         => __('Mode'),
                                    'value'         => old('strowallet_mode',@$api->config->strowallet_mode),
                                    'name'          => "strowallet_mode",
                                    'options'       => [__('Live') => global_const()::LIVE,__('Sandbox') => global_const()::SANDBOX]
                                ])
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-12 col-lg-12 form-group configForm" id="eversend">
                        <div class="row" >
                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 form-group">
                                <label>{{ __("Public Key") }}*</label>
                                <div class="input-group append">
                                    <span class="input-group-text"><i class="las la-key"></i></span>
                                    <input type="text" class="form--control" name="eversend_public_key" value="{{ @$api->config->eversend_public_key }}">
                                </div>
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 form-group">
                                <label>{{ __("Secret Key") }}*</label>
                                <div class="input-group append">
                                    <span class="input-group-text"><i class="las la-key"></i></span>
                                    <input type="text" class="form--control" name="eversend_secret_key" value="{{ @$api->config->eversend_secret_key }}">
                                </div>
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 form-group">
                                <label>{{ __("Base Url") }}*</label>
                                <div class="input-group append">
                                    <span class="input-group-text"><i class="las la-link"></i></span>
                                    <input type="text" class="form--control" name="eversend_url" value="{{ @$api->config->eversend_url }}">
                                </div>
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 form-group">
                                @include('admin.components.form.switcher', [
                                    'label'         => __('Mode'),
                                    'value'         => old('strowallet_mode',@$api->config->strowallet_mode),
                                    'name'          => "strowallet_mode",
                                    'options'       => [__('Live') => global_const()::LIVE,__('Sandbox') => global_const()::SANDBOX]
                                ])
                            </div>
                        </div>
                    </div>
                    <!--<div class="col-xl-12 col-lg-12 form-group">
                        @include('admin.components.form.input',[
                            'label'         =>__( 'Card Limit Within (1 to 3)'),
                            'name'          => 'card_limit',
                            'value'         => old('card_limit',@$api->card_limit),
                            'placeholder'   => "Enter 1-3 Only."
                        ])
                    </div>-->
                    
                    </div>
                    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 form-group">
                        <label class="form-check-label" for="flexCheckDefault">{{ __("is Created Card") }}*</label>
                        <div class="input-group append">
                            <span class="input-group-text"><i class="las la-link"></i></span>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_created_card" value="1" id="flexCheckDefault" @if($api->is_created_card) @checked(true) @endif>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 form-group">
                        <label class="form-check-label" for="flexCheckDefault">{{ __("is rechargeable Card") }}*</label>
                        <div class="input-group append">
                            <span class="input-group-text"><i class="las la-link"></i></span>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_rechargeable" value="1" id="flexCheckDefault" @if($api->is_rechargeable) @checked(true) @endif>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 form-group">
                        <label class="form-check-label" for="flexCheckDefault">{{ __("withdraw  money card") }}*</label>
                        <div class="input-group append">
                            <span class="input-group-text"><i class="las la-link"></i></span>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_withdraw" value="1" id="flexCheckDefault" @if($api->is_withdraw) @checked(true) @endif>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 form-group">
                        <label class="form-check-label" for="flexCheckDefault">{{ __("active Card") }}*</label>
                        <div class="input-group append">
                            <span class="input-group-text"><i class="las la-link"></i></span>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="flexCheckDefault" @if($api->is_active) @checked(true) @endif>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-12 col-lg-12 form-group">
                        @include('admin.components.form.input-text-rich',[
                            'label'         => __('Card Details').'*',
                            'name'          => 'card_details',
                            'value'         => old('card_details',@$api->card_details),
                            'placeholder'   => "Write Here...",
                        ])
                    </div>
                    <div class="col-xl-12 col-lg-12 form-group">
                        <label for="card-image">{{ __("Background Image") }}</label>
                        <div class="col-12 col-sm-6 m-auto">
                            @include('admin.components.form.input-file',[
                                'label'         => false,
                                'class'         => "file-holder m-auto",
                                'old_files_path'    => files_asset_path('card-api'),
                                'name'          => "image",
                                'old_files'         => old('image',@$api->image)
                            ])
                        </div>
                    </div>

                    <div class="col-xl-12 col-lg-12 form-group">
                        @include('admin.components.button.form-btn',[
                            'class'         => "w-100 btn-loading",
                            'text'          => __("Update"),
                            'permission'    => "admin.virtual.card.api.update"
                        ])
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
@push('script')
    <script>
        (function ($) {
            "use strict";
            var method = '{{ @$api->name}}';
            console.log('methode :',method);
            if (!method) {
                method = 'flutterwave';
            }

            apiMethod(method);
            $('select[name=api_method]').on('change', function() {
                var method = $(this).val();
                console.log(method);
                apiMethod(method);
            });

            function apiMethod(method){

                $('.configForm').addClass('d-none');
                if(method != 'other') {
                    console.log(method);
                    $(`#${method}`).removeClass('d-none');
                }
            }

        })(jQuery);

    </script>
@endpush
