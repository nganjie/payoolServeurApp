<div class="right">
    <div class="d-flex p-4 flex-column flex-md-row">
        
        <form class="card-form" action="{{ setRoute('admin.virtual.card.api.change') }}" method="POST" id='api_appForm'>
            @csrf
            @method("POST")
            <select class="js-select2 form--control nice-select" name="api_method_app" id="api_method_app">
                <option disabled>{{ __("Select Platfrom") }}</option>
                <option value="stripe" @if(getCurrentApi() == 'stripe') selected @endif>@lang('Stripe Api')</option>
                <option value="strowallet" @if(getCurrentApi() == 'strowallet') selected @endif>@lang('Strowallet Api')</option>
                <option value="sudo" @if(getCurrentApi() == 'sudo') selected @endif>@lang('Sudo Africa')</option>
                <option value="flutterwave" @if(getCurrentApi() == 'flutterwave') selected @endif>@lang('Flutterwave')</option>
                <option value="soleaspay" @if(getCurrentApi() == 'soleaspay') selected @endif>@lang('Soleaspay')</option>
                <option value="eversend" @if(getCurrentApi() == 'eversend') selected @endif>@lang('Eversend')</option>
            </select>
        </form>
        @push('script')
    <script>
        (function ($) {
            "use strict";
            var method = '{{ getCurrentApi()}}';
            if (!method) {
                method = 'flutterwave';
            }
            $("#submitButton").click(function() { $("#api_appForm").submit(); });
            $("#api_method_app").change(function() { $("#api_appForm").submit(); });


        })(jQuery);

    </script>
@endpush
        <div class="dashboard-path">
            @foreach ($breadcrumbs as $item)
                <span class="main-path"><a href="{{ $item['url'] }}">{{ $item['name'] }}</a></span>
                <i class="las la-angle-right"></i>
            @endforeach
            <span class="active-path">{{ $active ?? "" }}</span>
        </div>
    </div>
</div>