@extends('user.layouts.master')

@push('css')
<link rel="stylesheet" href="{{ asset('public/frontend/') }}/css/virtual-card.css">
@endpush

@section('breadcrumb')
    @include('user.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("user.dashboard"),
        ]
    ], 'active' => __(@$page_title)])
@endsection

@section('content')
<div class="body-wrapper">

    <div class="buy-card d-flex align-items-center justify-content-between ptb-40">
        <h3 class="title">{{ __(@$page_title) }} <i class="las la-arrow-right"></i></h3>
        <div class="dashboard-btn-wrapper">
            <div class="dashboard-btn">
                @if ($totalCards  < $card_limit )
                    <a href="javascript:void(0)" class="btn--base buyCard"> <i class="las la-plus"></i> {{__("Create A New Card")}}</a>
                @endif

            </div>
        </div>
    </div>
    <div class="testimonial-area">
        <div class="card-slider">
            <div class="swiper-wrapper">
                @forelse ($myCards ?? [] as $myCard)
                <div class="swiper-slide">
                    <div class="card-wrapper d-flex justify-content-between text-center align-items-center">
                        <div class="card-custom-area justify-content-center">
                            <div class="backgound">
                                <div class="left"></div>
                                <div class="right"></div>
                            </div>
                            <div class="card-custom">
                                <div class="flip">
                                    <div class="front bg_img" data-background="{{ get_image(@$cardApi->image ,'card-api') }}">
                                        <img class="logo" src="{{ get_fav($basic_settings) }}"
                                        alt="site-logo">
                                        <div class="investor">{{ @$basic_settings->site_name }}</div>
                                        <div class="chip">
                                            <div class="chip-line"></div>
                                            <div class="chip-line"></div>
                                            <div class="chip-line"></div>
                                            <div class="chip-line"></div>
                                            <div class="chip-main"></div>
                                        </div>
                                        @if(!$myCard->is_active)
                                        <div class="chip-line" style="margin:auto; background-color:#000;z-index:1000;">
                                            <p>{{__("Card has been blocked previously")}}</p>
                                        </div>
                                        @endif
                                        @if($myCard->is_non_subscription)
                                        <div class="chip-line" style="margin:auto; background-color:red;z-index:1000;">
                                            <p>{{__("Disabled")}}</p>
                                        </div>
                                        @endif
                                        <svg class="wave" viewBox="0 3.71 26.959 38.787" width="26.959" height="38.787" fill="white">
                                            <path d="M19.709 3.719c.266.043.5.187.656.406 4.125 5.207 6.594 11.781 6.594 18.938 0 7.156-2.469 13.73-6.594 18.937-.195.336-.57.531-.957.492a.9946.9946 0 0 1-.851-.66c-.129-.367-.035-.777.246-1.051 3.855-4.867 6.156-11.023 6.156-17.718 0-6.696-2.301-12.852-6.156-17.719-.262-.317-.301-.762-.102-1.121.204-.36.602-.559 1.008-.504z"></path>
                                            <path d="M13.74 7.563c.231.039.442.164.594.343 3.508 4.059 5.625 9.371 5.625 15.157 0 5.785-2.113 11.097-5.625 15.156-.363.422-1 .472-1.422.109-.422-.363-.472-1-.109-1.422 3.211-3.711 5.156-8.551 5.156-13.843 0-5.293-1.949-10.133-5.156-13.844-.27-.309-.324-.75-.141-1.114.188-.367.578-.582.985-.542h.093z"></path>
                                            <path d="M7.584 11.438c.227.031.438.144.594.312 2.953 2.863 4.781 6.875 4.781 11.313 0 4.433-1.828 8.449-4.781 11.312-.398.387-1.035.383-1.422-.016-.387-.398-.383-1.035.016-1.421 2.582-2.504 4.187-5.993 4.187-9.875 0-3.883-1.605-7.372-4.187-9.875-.321-.282-.426-.739-.266-1.133.164-.395.559-.641.984-.617h.094zM1.178 15.531c.121.02.238.063.344.125 2.633 1.414 4.437 4.215 4.437 7.407 0 3.195-1.797 5.996-4.437 7.406-.492.258-1.102.07-1.36-.422-.257-.492-.07-1.102.422-1.359 2.012-1.075 3.375-3.176 3.375-5.625 0-2.446-1.371-4.551-3.375-5.625-.441-.204-.676-.692-.551-1.165.122-.468.567-.785 1.051-.742h.094z"></path>
                                        </svg>
                                        @php
                                            $card_pan = str_split($myCard->number, 4);
                                        @endphp
                                        <div class="card-number">
                                            @foreach($card_pan as $key => $value)
                                            <div class="section">{{ $value }}</div>
                                            @endforeach
                                        </div>

                                        <div class="end"><span class="end-text">{{__("exp. end")}}:</span><span class="end-date"> {{ $myCard->expiration }}</span>
                                        </div>
                                        <div class="card-holder">{{ auth()->user()->fullname }}</div>
                                        @if($myCard->brand === "VISA")
                                        <div class="master">
                                            <img  src="{{ URL::to('/') }}/public/frontend/images/card/visa-logo.png"/>
                                        </div>
                                        @else
                                            <div class="master">
                                                <div class="circle master-red"></div>
                                                <div class="circle master-yellow"></div>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="back">
                                        <div class="strip-black"></div>
                                        <div class="ccv">
                                            <label>{{ __("ccv") }}</label>
                                            <div>{{ $myCard->mask }}</div>
                                        </div>
                                        <div class="terms">
                                            <p>
                                                @php
                                                    echo  @$card_details->card_details
                                                @endphp
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-balance">
                            <h2 class="title">{{__("Card Balance")}}</h2>
                            <span class="text--base">{{ getAmount(@$myCard->amount,2) }}{{ get_default_currency_symbol() }}</span>
                            <div class="card-content d-flex justify-content-center mt-3">
                                <div class="card-details">
                                    <a href="{{ setRoute('user.eversend.virtual.card.details',$myCard->card_id) }}">
                                        <div class="details-icon">
                                            <i class="las la-info-circle"></i>
                                        </div>
                                        <h5 class="title">{{ __("Details") }}</h5>
                                    </a>
                                </div>
                                <div class="card-details">
                                    @if($myCard->is_non_subscription == true )
                                        <a href="javascript:void(0)" class=" active-deactive-btn" data-id="{{ $myCard->id }}">
                                        <div class="details-icon">
                                            <i class="fas fa-times-circle me-1"></i>
                                        </div>
                                        <h5 class="title">{{ __("remove Default") }}</h5>
                                    </a>
                                    @else
                                        <a href="javascript:void(0)" class=" active-deactive-btn" data-id="{{ $myCard->id }}">
                                            <div class="details-icon">
                                                <i class="fas fa-check-circle me-1"></i>
                                            </div>
                                            <h5 class="title">{{ __("make Default") }}</h5>
                                        </a>
                                    @endif
                                </div>
                                <div class="card-details">
                                    <a href="javascript:void(0)" class="fundCard" data-id="{{ $myCard->id }}">
                                        <div class="details-icon">
                                            <i class="las la-coins"></i>
                                        </div>
                                        <h5 class="title">{{ __("Fund") }}</h5>
                                    </a>
                                </div>
                                <div class="card-details">
                                    <a href="{{  setRoute('user.eversend.virtual.card.transaction',$myCard->card_id) }}">
                                        <div class="details-icon">
                                            <i class="menu-icon las la-recycle"></i>
                                        </div>
                                        <h5 class="title">{{__("Transactions")}}</h5>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="swiper-slide">
                    <div class="card-wrapper d-flex justify-content-between text-center align-items-center">
                            {{ __("No Virtual Card Created!") }}
                    </div>
                </div>
                @endforelse


            </div>
            <div class="swiper-pagination"></div>
        </div>
    </div>
    @if(count(@$myCards) > 0)
    <div class="dashboard-list-area mt-20">
        <div class="dashboard-header-wrapper">
            <h4 class="title ">{{__("Recent Transaction")}}</h4>
            <div class="dashboard-btn-wrapper">
                <div class="dashboard-btn mb-2">
                    <a href="{{ setRoute('user.transactions.index','virtual-card') }}" class="btn--base">{{__("View More")}}</a>
                </div>
            </div>
        </div>
        <div class="dashboard-list-wrapper">
            @include('user.components.transaction-log',compact("transactions"))
        </div>
    </div>
    @endif

</div>

<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start Modal
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
<div class="modal fade" id="BuyCardModal" tabindex="-1" aria-labelledby="buycard-modal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" id="buycard-modal">
                <h4 class="modal-title">{{__("Add Card")}}</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="las la-times"></i></button>
            </div>
            <div class="modal-body">

                    <form class="card-form row g-4" action="{{ route('user.eversend.virtual.card.create') }}" method="POST">
                        @csrf
                    <div class="col-12">
                        <div class="virtual-card-wrapper d-flex justify-content-center mb-20">
                            <div class="dash-payment-body">
                                <div class="card-custom">
                                    <div class="flip">
                                        <div class="front bg_img" data-background="{{ get_image(@$cardApi->image ,'card-api') }}">
                                            <img class="logo" src="{{ get_fav($basic_settings) }}"
                                            alt="site-logo">
                                            <div class="investor">{{ @$basic_settings->site_name }}</div>
                                            <div class="chip">
                                                <div class="chip-line"></div>
                                                <div class="chip-line"></div>
                                                <div class="chip-line"></div>
                                                <div class="chip-line"></div>
                                                <div class="chip-main"></div>
                                            </div>
                                            <svg class="wave" viewBox="0 3.71 26.959 38.787" width="26.959" height="38.787" fill="white">
                                                <path d="M19.709 3.719c.266.043.5.187.656.406 4.125 5.207 6.594 11.781 6.594 18.938 0 7.156-2.469 13.73-6.594 18.937-.195.336-.57.531-.957.492a.9946.9946 0 0 1-.851-.66c-.129-.367-.035-.777.246-1.051 3.855-4.867 6.156-11.023 6.156-17.718 0-6.696-2.301-12.852-6.156-17.719-.262-.317-.301-.762-.102-1.121.204-.36.602-.559 1.008-.504z"></path>
                                                <path d="M13.74 7.563c.231.039.442.164.594.343 3.508 4.059 5.625 9.371 5.625 15.157 0 5.785-2.113 11.097-5.625 15.156-.363.422-1 .472-1.422.109-.422-.363-.472-1-.109-1.422 3.211-3.711 5.156-8.551 5.156-13.843 0-5.293-1.949-10.133-5.156-13.844-.27-.309-.324-.75-.141-1.114.188-.367.578-.582.985-.542h.093z"></path>
                                                <path d="M7.584 11.438c.227.031.438.144.594.312 2.953 2.863 4.781 6.875 4.781 11.313 0 4.433-1.828 8.449-4.781 11.312-.398.387-1.035.383-1.422-.016-.387-.398-.383-1.035.016-1.421 2.582-2.504 4.187-5.993 4.187-9.875 0-3.883-1.605-7.372-4.187-9.875-.321-.282-.426-.739-.266-1.133.164-.395.559-.641.984-.617h.094zM1.178 15.531c.121.02.238.063.344.125 2.633 1.414 4.437 4.215 4.437 7.407 0 3.195-1.797 5.996-4.437 7.406-.492.258-1.102.07-1.36-.422-.257-.492-.07-1.102.422-1.359 2.012-1.075 3.375-3.176 3.375-5.625 0-2.446-1.371-4.551-3.375-5.625-.441-.204-.676-.692-.551-1.165.122-.468.567-.785 1.051-.742h.094z"></path>
                                            </svg>
                                            <div class="card-number">
                                                <div class="section">0000</div>
                                                <div class="section">0000</div>
                                                <div class="section">0000</div>
                                                <div class="section">0000</div>
                                            </div>
                                            <div class="end"><span class="end-text">{{__("exp. end")}}:</span><span class="end-date"> 00/00</span>
                                            </div>
                                            <div class="card-holder">{{ auth()->user()->fullname }}</div>
                                            <div class="master">
                                                <h3 class="title">{{ __("VISA") }}</h4>
                                            </div>
                                        </div>
                                        <div class="back">
                                            <div class="strip-black"></div>
                                            <div class="ccv">
                                                <label>{{ __("ccv") }}</label>
                                                <div>000</div>
                                            </div>
                                            <div class="terms">
                                                @php
                                                echo @$cardApi->card_details;
                                                @endphp
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group">
                                <label>{{__("Card Amount")}}<span>*</span></label>
                                <input type="number" class="form--control" required placeholder="{{ __("Enter Amount") }}" name="card_amount" value="{{ old("card_amount") }}">
                                <div class="currency">
                                    <p>{{ get_default_currency_code() }}</p>
                                </div>
                                
                               <div class="d-flex justify-content-between">
                                <code class="d-block mt-3  text--base fw-bold balance-show limit-show">--</code>
                                <code class="d-block mt-3  text--base fw-bold balance-show">{{ __(" Balance: ") }} {{ authWalletBalance() }} {{ get_default_currency_code() }}</code>
                               </div>
                            </div>
                            
                            <div class="row">
                                    <div class="col-md-6 col-lg-6">
                                                @include('admin.components.form.select',[
                                                    'label'         => __("Category"),
                                                    'name'          => "card_category",
                                                    'value'         => "PERSONAL",
                                                    'options'       => ['Personal' => 'PERSONAL'],
                                                    'multiple'      => false,
                                                    'class'     => "form--control"
                                                ])
                                    </div>
                                    <div class="col-md-6 col-lg-6">
                                                @include('admin.components.form.select',[
                                                    'label'         => __("Card Type"),
                                                    'name'          => "card_type",
                                                    'value'         => "VISA",
                                                    'options'       => [ 'Visa' => 'VISA', 'Mastercard' => 'MASTERCARD'],
                                                    'multiple'      => false,
                                                    'class'     => "form--control"
                                                ])
                                    </div>
                                    @if (!@$user->eversend_customer)
                                    <div class="col-md-6 col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __("first Name") }}<span>*</span></label>
                                            <input type="text" class="form--control" placeholder="{{__("first Name")}}" name="first_name" value="{{ auth()->user()->firstname }}" required="yes">
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __("last Name") }}<span>*</span></label>
                                            <input type="text" class="form--control" placeholder="{{ __("last Name") }}" name="last_name" value="{{ auth()->user()->lastname }}" required="yes">
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __("Email") }} <span>*</span></label>
                                            <input type="email" class="form--control" placeholder="{{ __("Enter Email") }}" name="email" value="{{ auth()->user()->email }}" required="yes">
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-6">
                                        <div class="form-group">
                                            <label>{{__("Date Of Birth")}}<span>*</span></label>
                                            <input type="date" class="form--control" placeholder="{{__("Birth Date")}}" name="dob" required="yes">
                                        </div>
                                    </div>
                                    <div class="col-md-12 col-lg-12">
                                        @include('admin.components.form.input',[
                                            'label'         => __("Numéro Pièce D'identité (Le premier chiffre)"),
                                            'name'          => "id_number",
                                            'placeholder'   =>__("Entrez Numéro Pièce D'identité"),
                                            'required'       => true,
                                        ])
                                    </div>
                                    <!-- <div class="col-md-6 col-lg-6">
                                        @include('admin.components.form.input',[
                                            'label'         => __("Country Code"),
                                            'name'          => "country_code",
                                            'placeholder'   =>__("Enter Country Code"),
                                            'value'         => old('state',auth()->user()->address->country_code ?? ""),
                                            'required'       => true
                                        ])
                                    </div>
                                    <div class="col-md-6 col-lg-6">
                                        @include('admin.components.form.input',[
                                            'label'         => __("State"),
                                            'name'          => "billing_state",
                                            'placeholder'   =>__("Enter State"),
                                            'value'         => old('state',auth()->user()->address->state ?? ""),
                                            'required'       => true
                                        ])
                                    </div>
                                    <div class="col-md-6 col-lg-6">
                                        @include('admin.components.form.input',[
                                            'label'         => __("Country"),
                                            'name'          => "billing_country",
                                            'placeholder'   =>__("Enter Country"),
                                            'value'         => old('state',auth()->user()->address->country ?? ""),
                                            'required'       => true
                                        ])
                                    </div>
                                    <div class="col-md-6 col-lg-6">
                                        @include('admin.components.form.input',[
                                            'label'         =>__( "City"),
                                            'name'          => "billing_city",
                                            'placeholder'   => __("Enter City"),
                                            'value'         => old('city',auth()->user()->address->city ?? ""),
                                            'required'       => true
                                        ])
                                    </div>
                                    <div class="col-md-6 col-lg-6">
                                        @include('admin.components.form.input',[
                                            'label'         => __("Address"),
                                            'name'          => "billing_adress",
                                            'placeholder'   => __("Enter Address"),
                                            'value'         => old('address',auth()->user()->address->address ?? ""),
                                            'required'       => true
                                        ])
                                    </div>
                                    <div class="col-md-6 col-lg-6">
                                        @include('admin.components.form.input',[
                                            'label'         => __("Zip Code"),
                                            'name'          => "billing_postal_code",
                                            'placeholder'   => __("Enter Zip Code"),
                                            'value'         => old('zip_code',auth()->user()->address->zip ?? ""),
                                            'required'       => true
                                        ])
                                    </div> -->
                                   @endif

                                </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="ps-4">
                            <div class="d-flex justify-content-between">
                                <h3 class="fs-6 fw-lighter py-1 text-capitalize">&bull; {{ __("Total Charge") }} :
                                </h3>
                                <h3 class="fs-6 fw-lighter py-1 text-capitalize fees-show">--</h3>
                            </div>
                            <div class="d-flex justify-content-between">
                                <h3 class="fs-6 fw-lighter py-1 text-capitalize">&bull; {{__("Total Pay")}} :
                                </h3>
                                <h3 class="fs-6 fw-lighter py-1 text-capitalize payable-total">--</h3>
                            </div>

                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn--base w-100 btn-loading buyBtn">{{ __("Confirm") }}</button>
                    </div>
                </form>

            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="FundCardModal" tabindex="-1" aria-labelledby="card-modal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" id="buycard-modal">
                <h4 class="modal-title">{{__("Fund Card")}}</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="las la-times"></i></button>
            </div>
            <div class="modal-body">

                    <form class="card-form row g-4" action="{{ route('user.eversend.virtual.card.fund') }}" method="POST">
                        @csrf
                        <input type="hidden" name="id">
                    <div class="col-12">
                        <div class="row">
                            <div class="form-group">
                                <label>{{__("Fund Amount")}}<span>*</span></label>
                                <input type="number" class="form--control" required placeholder="{{ __("Enter Amount") }}" name="fund_amount" value="{{ old("fund_amount") }}">
                                <div class="currency">
                                    <p>{{ get_default_currency_code() }}</p>
                                </div>
                                
                               <div class="d-flex justify-content-between">
                                <code class="d-block mt-3  text--base fw-bold balance-show fund-limit-show">--</code>
                                <code class="d-block mt-3  text--base fw-bold balance-show">{{ __(" Balance: ") }} {{ authWalletBalance() }} {{ get_default_currency_code() }}</code>
                               </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="ps-4">
                            <div class="d-flex justify-content-between">
                                <h3 class="fs-6 fw-lighter py-1 text-capitalize">&bull; {{ __("Total Charge") }} :
                                </h3>
                                <h3 class="fs-6 fw-lighter py-1 text-capitalize fund-fees-show">--</h3>
                            </div>
                            <div class="d-flex justify-content-between">
                                <h3 class="fs-6 fw-lighter py-1 text-capitalize">&bull; {{__("Total Pay")}} :
                                </h3>
                                <h3 class="fs-6 fw-lighter py-1 text-capitalize fund-payable-total">--</h3>
                            </div>

                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn--base w-100 btn-loading fund-btn">{{ __("Confirm") }}</button>
                    </div>
                </form>

            </div>

        </div>
    </div>
</div>

<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    End Modal
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->

@endsection

@push('script')
    <script>
        $(".active-deactive-btn").click(function(){
            var actionRoute =  "{{ setRoute('user.eversend.virtual.card.make.default.or.remove') }}";
            var target = $(this).data('id');
            var btnText = $(this).text();
            var message     = `Are you sure to <strong>${btnText}</strong> this card?`;
            openAlertModal(actionRoute,target,message,btnText,"POST");
        });
    </script>
    <script>
        var defualCurrency = "{{ get_default_currency_code() }}";
        var defualCurrencyRate = "{{ get_default_currency_rate() }}";
        $('.buyCard').on('click', function () {
            var modal = $('#BuyCardModal');
            $(document).ready(function(){
                getLimit();
                getFees();
                getPreview();
            });
            $("input[name=card_amount]").keyup(function(){
                    getFees();
                    getPreview();
            });
            $("input[name=card_amount]").focusout(function(){
                    enterLimit();
            });
            function getLimit() {
                var currencyCode = acceptVar().currencyCode;
                var currencyRate = acceptVar().currencyRate;

                var min_limit = acceptVar().currencyMinAmount;
                var max_limit =acceptVar().currencyMaxAmount;
                if($.isNumeric(min_limit) || $.isNumeric(max_limit)) {
                    var min_limit_calc = parseFloat(min_limit/currencyRate).toFixed(2);
                    var max_limit_clac = parseFloat(max_limit/currencyRate).toFixed(2);
                    $('.limit-show').html("{{ __('Limit') }} "+min_limit_calc + " " + currencyCode + " - " + max_limit_clac + " " + currencyCode);

                    return {
                        minLimit:min_limit_calc,
                        maxLimit:max_limit_clac,
                    };
                }else {
                    $('.limit-show').html("--");
                    return {
                        minLimit:0,
                        maxLimit:0,
                    };
                }
            }
            function acceptVar() {

                var currencyCode = defualCurrency;
                var currencyRate = defualCurrencyRate;
                var currencyMinAmount ="{{getAmount($cardCharge->min_limit)}}";
                var currencyMaxAmount = "{{getAmount($cardCharge->max_limit)}}";
                var currencyFixedCharge = "{{getAmount($cardCharge->fixed_charge)}}";
                var currencyPercentCharge = "{{getAmount($cardCharge->percent_charge)}}";


                return {
                    currencyCode:currencyCode,
                    currencyRate:currencyRate,
                    currencyMinAmount:currencyMinAmount,
                    currencyMaxAmount:currencyMaxAmount,
                    currencyFixedCharge:currencyFixedCharge,
                    currencyPercentCharge:currencyPercentCharge,


                };
            }
            function feesCalculation() {
                var currencyCode = acceptVar().currencyCode;
                var currencyRate = acceptVar().currencyRate;
                var sender_amount = $("input[name=card_amount]").val();
                sender_amount == "" ? (sender_amount = 0) : (sender_amount = sender_amount);

                var fixed_charge = acceptVar().currencyFixedCharge;
                var percent_charge = acceptVar().currencyPercentCharge;

                if ($.isNumeric(percent_charge) && $.isNumeric(fixed_charge) && $.isNumeric(sender_amount)) {
                    // Process Calculation
                    var fixed_charge_calc = parseFloat(currencyRate * fixed_charge);

                    var percent_charge_calc = (parseFloat(sender_amount) / 100) * parseFloat(percent_charge);
                    var total_charge = parseFloat(fixed_charge_calc) + parseFloat(percent_charge_calc);
                    total_charge = parseFloat(total_charge).toFixed(2);
                    // return total_charge;
                    return {
                        total: total_charge,
                        fixed: fixed_charge_calc,
                        percent: percent_charge,
                    };
                } else {
                    // return "--";
                    return false;
                }
            }

            function getFees() {
                var currencyCode = acceptVar().currencyCode;
                var percent = acceptVar().currencyPercentCharge;
                var charges = feesCalculation();
                if (charges == false) {
                    return false;
                }
                $(".fees-show").html( parseFloat(charges.fixed).toFixed(2) + " " + currencyCode + " + " + parseFloat(charges.percent).toFixed(2) + "% = " + parseFloat(charges.total).toFixed(2) + " " + currencyCode);
            }
            function getPreview() {
                    var senderAmount = $("input[name=card_amount]").val();
                    var charges = feesCalculation();
                    var sender_currency = acceptVar().currencyCode;
                    var sender_currency_rate = acceptVar().currencyRate;

                    senderAmount == "" ? senderAmount = 0 : senderAmount = senderAmount;
                    // Sending Amount
                    $('.request-amount').html("Card Amount: " + senderAmount + " " + sender_currency);

                        // Fees
                        var charges = feesCalculation();
                    var total_charge = 0;
                    if(senderAmount == 0){
                        total_charge = 0;
                    }else{
                        total_charge = charges.total;
                    }
                    $('.fees').html("Total Charge: " + total_charge + " " + sender_currency);
                    var totalPay = parseFloat(senderAmount) * parseFloat(sender_currency_rate)
                    var pay_in_total = 0;
                    if(senderAmount == 0 ||  senderAmount == ''){
                            pay_in_total = 0;
                    }else{
                            pay_in_total =  parseFloat(totalPay) + parseFloat(charges.total);
                    }
                    $('.payable-total').html( pay_in_total + " " + sender_currency);

            }
            function enterLimit(){
                var min_limit = parseFloat("{{getAmount($cardCharge->min_limit)}}");
                var max_limit =parseFloat("{{getAmount($cardCharge->max_limit)}}");
                var currencyRate = acceptVar().currencyRate;
                var sender_amount = parseFloat($("input[name=card_amount]").val());

                if( sender_amount < min_limit ){
                    throwMessage('error',["{{ __('Please follow the mimimum limit') }}"]);
                    $('.buyBtn').attr('disabled',true)
                }else if(sender_amount > max_limit){
                    throwMessage('error',["{{ __('Please follow the maximum limit') }}"]);
                    $('.buyBtn').attr('disabled',true)
                }else{
                    $('.buyBtn').attr('disabled',false)
                }

            }
                modal.modal('show');
        });
        $('.fundCard').on('click', function () {
            var modal = $('#FundCardModal');
            modal.find('input[name=id]').val($(this).data('id'));
            $(document).ready(function(){
                getLimit();
                getFees();
                getPreview();
            });
            $("input[name=fund_amount]").keyup(function(){
                getFees();
                getPreview();
            });
            $("input[name=fund_amount]").focusout(function(){
                enterLimit();
            });

            function getLimit() {
                var currencyCode = acceptVar().currencyCode;
                var currencyRate = acceptVar().currencyRate;

                var min_limit = acceptVar().currencyMinAmount;
                var max_limit =acceptVar().currencyMaxAmount;
                if($.isNumeric(min_limit) || $.isNumeric(max_limit)) {
                    var min_limit_calc = parseFloat(min_limit/currencyRate).toFixed(2);
                    var max_limit_clac = parseFloat(max_limit/currencyRate).toFixed(2);
                    $('.fund-limit-show').html("{{ __('Limit') }} " + min_limit_calc + " " + currencyCode + " - " + max_limit_clac + " " + currencyCode);

                    return {
                        minLimit:min_limit_calc,
                        maxLimit:max_limit_clac,
                    };
                }else {
                    $('.fund-limit-show').html("--");
                    return {
                        minLimit:0,
                        maxLimit:0,
                    };
                }
            }
            function acceptVar() {

                var currencyCode = defualCurrency;
                var currencyRate = defualCurrencyRate;
                var currencyMinAmount ="{{getAmount($cardReloadCharge->min_limit)}}";
                var currencyMaxAmount = "{{getAmount($cardReloadCharge->max_limit)}}";
                var currencyFixedCharge = "{{getAmount($cardReloadCharge->fixed_charge)}}";
                var currencyPercentCharge = "{{getAmount($cardReloadCharge->percent_charge)}}";


                return {
                    currencyCode:currencyCode,
                    currencyRate:currencyRate,
                    currencyMinAmount:currencyMinAmount,
                    currencyMaxAmount:currencyMaxAmount,
                    currencyFixedCharge:currencyFixedCharge,
                    currencyPercentCharge:currencyPercentCharge,


                };
            }
            function feesCalculation() {
                var currencyCode = acceptVar().currencyCode;
                var currencyRate = acceptVar().currencyRate;
                var sender_amount = $("input[name=fund_amount]").val();
                sender_amount == "" ? (sender_amount = 0) : (sender_amount = sender_amount);

                var fixed_charge = acceptVar().currencyFixedCharge;
                var percent_charge = acceptVar().currencyPercentCharge;
                if ($.isNumeric(percent_charge) && $.isNumeric(fixed_charge) && $.isNumeric(sender_amount)) {
                    // Process Calculation
                    var fixed_charge_calc = parseFloat(currencyRate * fixed_charge);
                    var percent_charge_calc = parseFloat(currencyRate)*(parseFloat(sender_amount) / 100) * parseFloat(percent_charge);
                    var total_charge = parseFloat(fixed_charge_calc) + parseFloat(percent_charge_calc);
                    total_charge = parseFloat(total_charge).toFixed(2);
                    // return total_charge;
                    return {
                        total: total_charge,
                        fixed: fixed_charge_calc,
                        percent: percent_charge,
                    };
                } else {
                    // return "--";
                    return false;
                }
            }

            function getFees() {
                var currencyCode = acceptVar().currencyCode;
                var percent = acceptVar().currencyPercentCharge;
                var charges = feesCalculation();
                if (charges == false) {
                    return false;
                }
                $(".fund-fees-show").html( parseFloat(charges.fixed).toFixed(2) + " " + currencyCode + " + " + parseFloat(charges.percent).toFixed(2) + "% = " + parseFloat(charges.total).toFixed(2) + " " + currencyCode);
            }
            function getPreview() {
                    var senderAmount = $("input[name=fund_amount]").val();
                    var charges = feesCalculation();
                    var sender_currency = acceptVar().currencyCode;
                    var sender_currency_rate = acceptVar().currencyRate;

                    senderAmount == "" ? senderAmount = 0 : senderAmount = senderAmount;
                    // Sending Amount
                    // Fees
                    var charges = feesCalculation();

                    var totalPay = parseFloat(senderAmount) * parseFloat(sender_currency_rate)
                    var pay_in_total = 0;
                    if(senderAmount == 0 ||  senderAmount == ''){
                        pay_in_total = 0;
                    }else{
                        pay_in_total =  parseFloat(totalPay) + parseFloat(charges.total);
                    }
                    $('.fund-payable-total').html( pay_in_total + " " + sender_currency);

            }
            function enterLimit(){
                var min_limit = parseFloat("{{getAmount($cardReloadCharge->min_limit)}}");
                var max_limit =parseFloat("{{getAmount($cardReloadCharge->max_limit)}}");
                var currencyRate = acceptVar().currencyRate;
                var sender_amount = parseFloat($("input[name=fund_amount]").val());

                if( sender_amount < min_limit ){
                    throwMessage('error',["{{ __('Please follow the mimimum limit') }}"]);
                    $('.fund-btn').attr('disabled',true)
                }else if(sender_amount > max_limit){
                    throwMessage('error',["{{ __('Please follow the maximum limit') }}"]);
                    $('.fund-btn').attr('disabled',true)
                }else{
                    $('.fund-btn').attr('disabled',false)
                }

            }
            modal.modal('show');
        });
    </script>
@endpush