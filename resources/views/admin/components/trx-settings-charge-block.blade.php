<div class="custom-card mb-10">
    <div class="card-header">
        @if($title=="Virtual Card Charges"||$title=="Card Reload Charges")
           <h6 class="title">{{ __($title).' '.getCurrentApi() ?? "" }}</h6>
        @else
        <h6 class="title">{{ __($title) ?? "" }}</h6>
        @endif
    </div>
    <div class="card-body">
        <form class="card-form" method="POST" action="{{ $route ?? "" }}">
            @csrf
            @method("PUT")

            <input type="hidden" value="{{ $item->slug }}" name="slug">
                 @php
                    if($item->slug == 'virtual_card_'.getCurrentApi() || $item->slug == 'virtual_card_withdraw'.getCurrentApi()){
                        $colLg = 'col-xl-6';
                        $colXl = 'col-lg-6';
                    }else{
                        $colLg = 'col-xl-6';
                        $colXl = 'col-lg-6';
                    }
                @endphp
            <div class="row">
                <div class="{{$colLg }} {{ $colXl }} mb-10">
                    <div class="custom-inner-card">
                        <div class="card-inner-header">
                            <h5 class="title">{{ __("Charges") }}</h5>
                        </div>
                        <div class="card-inner-body">
                            <div class="row">
                                <div class="col-xxl-12 col-xl-6 col-lg-6 form-group">
                                    <label>{{ __("Fixed Charge") }}*</label>
                                    <div class="input-group">
                                        <input type="number" class="form--control" value="{{ old($data->slug.'_fixed_charge',$data->fixed_charge) }}" name="{{$data->slug}}_fixed_charge">
                                        <span class="input-group-text">{{ get_default_currency_code($default_currency) }}</span>
                                    </div>
                                </div>
                                <div class="col-xxl-12 col-xl-6 col-lg-6 form-group">
                                    <label>{{ __("Percent Charge") }}*</label>
                                    <div class="input-group">
                                        <input type="number" class="form--control" value="{{ old($data->slug.'_percent_charge',$data->percent_charge) }}" name="{{$data->slug}}_percent_charge">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                @if($item->slug == 'virtual_card_eversend'&&getCurrentApi()=="eversend")
                                    <div class="col-xxl-12 col-xl-6 col-lg-6 form-group">
                                        <label>{{ __("One-time Payment") }}*</label>
                                        <div class="input-group">
                                            <input type="number" class="form--control" value="{{ old($data->slug.'_fixed_final_charge',$data->fixed_final_charge) }}" name="{{$data->slug}}_fixed_final_charge">
                                            <span class="input-group-text"></span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="{{$colLg }} {{ $colXl }} mb-10">
                    <div class="custom-inner-card">
                        <div class="card-inner-header">
                            <h5 class="title">{{ __("Range") }}</h5>
                        </div>
                        <div class="card-inner-body">
                            <div class="row">
                                <div class="col-xxl-12 col-xl-6 col-lg-6  form-group">
                                    <label>{{ __("Minimum Amount") }}</label>
                                    <div class="input-group">
                                        <input type="number" class="form--control" value="{{ old($data->slug.'_min_limit',$data->min_limit) }}" name="{{$data->slug}}_min_limit">
                                        <span class="input-group-text">{{ get_default_currency_code($default_currency) }}</span>
                                    </div>
                                </div>
                                <div class="col-xxl-12 col-xl-6 col-lg-6 form-group">
                                    <label>{{ __("Maximum Amount") }}</label>
                                    <div class="input-group">
                                        <input type="number" class="form--control" value="{{ old($data->slug.'_max_limit',$data->max_limit) }}" name="{{$data->slug}}_max_limit">
                                        <span class="input-group-text">{{ get_default_currency_code($default_currency) }}</span>
                                    </div>
                                </div>
                                @if($item->slug == 'virtual_card_eversend'&&getCurrentApi()=="eversend")
                                    <div class="col-xxl-12 col-xl-6 col-lg-6 form-group">
                                        <label>{{ __("Monthly Fee") }}*</label>
                                        <div class="input-group">
                                            <input type="number" class="form--control" value="{{ old($data->slug.'_fixed_month_charge',$data->fixed_month_charge) }}" name="{{$data->slug}}_fixed_month_charge">
                                            <span class="input-group-text"></span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="row mb-10-none">
                <div class="col-xl-12 col-lg-12 form-group">
                    @include('admin.components.button.form-btn',[
                        'text'          => __("Update"),
                        'class'         => "w-100 btn-loading",
                        'permission'    => "admin.trx.settings.charges.update",
                    ])
                </div>
            </div>
        </form>
    </div>
</div>
