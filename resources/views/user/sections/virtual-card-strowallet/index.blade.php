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
                @if ($customer_card  < $card_limit )
                <a href="{{ setRoute('user.strowallet.virtual.card.create') }}" class="btn--base"> <i class="las la-plus"></i> {{__("Create A New Card")}}</a>
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
                                        @if($myCard->card_status=="active")
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
                                        @endif
                                        @if(!($myCard->card_status=="active"))
                                        <div  style="margin:0 auto;z-index:1000;bottom:30; width:200px;height:200px;">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="white" style="margin-top:40px;" width="150px" height="100px" viewBox="0 0 448 512"><!--!Font Awesome Free 6.7.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M144 144l0 48 160 0 0-48c0-44.2-35.8-80-80-80s-80 35.8-80 80zM80 192l0-48C80 64.5 144.5 0 224 0s144 64.5 144 144l0 48 16 0c35.3 0 64 28.7 64 64l0 192c0 35.3-28.7 64-64 64L64 512c-35.3 0-64-28.7-64-64L0 256c0-35.3 28.7-64 64-64l16 0z"/></svg>
                                        </div>
                                        @endif
                                        @if($myCard->card_status=="active")
                                        <svg class="wave" viewBox="0 3.71 26.959 38.787" width="26.959" height="38.787" fill="white">
                                            <path d="M19.709 3.719c.266.043.5.187.656.406 4.125 5.207 6.594 11.781 6.594 18.938 0 7.156-2.469 13.73-6.594 18.937-.195.336-.57.531-.957.492a.9946.9946 0 0 1-.851-.66c-.129-.367-.035-.777.246-1.051 3.855-4.867 6.156-11.023 6.156-17.718 0-6.696-2.301-12.852-6.156-17.719-.262-.317-.301-.762-.102-1.121.204-.36.602-.559 1.008-.504z"></path>
                                            <path d="M13.74 7.563c.231.039.442.164.594.343 3.508 4.059 5.625 9.371 5.625 15.157 0 5.785-2.113 11.097-5.625 15.156-.363.422-1 .472-1.422.109-.422-.363-.472-1-.109-1.422 3.211-3.711 5.156-8.551 5.156-13.843 0-5.293-1.949-10.133-5.156-13.844-.27-.309-.324-.75-.141-1.114.188-.367.578-.582.985-.542h.093z"></path>
                                            <path d="M7.584 11.438c.227.031.438.144.594.312 2.953 2.863 4.781 6.875 4.781 11.313 0 4.433-1.828 8.449-4.781 11.312-.398.387-1.035.383-1.422-.016-.387-.398-.383-1.035.016-1.421 2.582-2.504 4.187-5.993 4.187-9.875 0-3.883-1.605-7.372-4.187-9.875-.321-.282-.426-.739-.266-1.133.164-.395.559-.641.984-.617h.094zM1.178 15.531c.121.02.238.063.344.125 2.633 1.414 4.437 4.215 4.437 7.407 0 3.195-1.797 5.996-4.437 7.406-.492.258-1.102.07-1.36-.422-.257-.492-.07-1.102.422-1.359 2.012-1.075 3.375-3.176 3.375-5.625 0-2.446-1.371-4.551-3.375-5.625-.441-.204-.676-.692-.551-1.165.122-.468.567-.785 1.051-.742h.094z"></path>
                                        </svg>
                                        @if ($myCard->card_number)
                                            @php
                                                $card_pan = str_split($myCard->card_number, 4);
                                            @endphp
                                            <div class="card-number">
                                                @foreach($card_pan as $key => $value)
                                                    <div class="section">{{ $value }}</div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="card-number">
                                                <div class="section">----</div>
                                                <div class="section">----</div>
                                                <div class="section">----</div>
                                                <div class="section">----</div>
                                            </div>
                                        @endif
                                        <div class="end"><span class="end-text">{{__("exp. end")}} : </span><span class="end-date"> {{ $myCard->expiry ?? 'mm/yyyy' }}</span>
                                        </div>
                                        <div class="card-holder">{{ auth()->user()->fullname }}</div>
                                        <div class="master">
                                            @if ($myCard->card_brand == 'visa')
                                                <img src="{{ asset('public/frontend/images/card/visa-logo.png') }}" alt="card">
                                            @else
                                                <img src="{{ asset('public/frontend/images/card/Mastercard-logo.png') }}" alt="card">
                                            @endif
                                        </div>
                                        @endif
                                    </div>
                                    <div class="back">
                                        <div class="strip-black"></div>
                                        <div class="ccv">
                                            <label>{{ __("ccv") }}</label>
                                            @if($myCard->card_status=="active")
                                            <div>{{ $myCard->cvv ?? '---' }}</div>
                                            @endif
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
                            <h5 class="title"> {{ __("My Card")." (" }}{{ count($myCards)."/".$cardApi->card_limit.")"}}</h5>
                            <h2 class="title">{{__("Card Balance")}}</h2>
                            @php
                                $live_card_data = card_details($myCard->card_id,$card_api->config->strowallet_public_key,$card_api->config->strowallet_url);
                            @endphp

                            <span class="text--base">{{ getAmount(updateStroWalletCardBalance(auth()->user(),$myCard->card_id,$live_card_data),2) }}{{ get_default_currency_symbol() }}</span>
                            @if ($myCard->card_status == 'pending')
                                <div class="status mt-10">
                                    <small class="badge badge--warning">Pending</small>
                                </div>
                            @endif
                            
                            <div class="card-content d-flex justify-content-center mt-3">
                                @if($myCard->is_penalize)
                                <div class="card-details">
                                    <div class="payPenalityModal" data-id="{{ $myCard->id }}">
                                        <div class="details-icon">
                                            <i class="fa fa-unlock"></i>
                                        </div>
                                        <h5 class="title">{{ __("unblock") }}</h5>
                                    </div>
                                </div>
                                @else
                                <div class="card-details">
                                    <a href="{{ setRoute('user.strowallet.virtual.card.details',$myCard->card_id) }}">
                                        <div class="details-icon">
                                            <i class="las la-info-circle"></i>
                                        </div>
                                        <h5 class="title">{{ __("Details") }}</h5>
                                    </a>
                                </div>
                                <div class="card-details">
                                    @if($myCard->is_default == true )
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
                                    <a href="javascript:void(0)" class="strowalletFundCard" data-id="{{ $myCard->id }}">
                                        <div class="details-icon">
                                            <i class="las la-coins"></i>
                                        </div>
                                        <h5 class="title">{{ __("Fund") }}</h5>
                                    </a>
                                </div>
                                <div class="card-details">
                                    <a href="javascript:void(0)" class="withdrawCard" data-id="{{ $myCard->id }}" data-amount="{{$myCard->balance}}">
                                        <div class="details-icon">
                                            <i class="las la-coins"></i>
                                        </div>
                                        <h5 class="title">{{ __("withdraw") }}</h5>
                                    </a>
                                </div>
                                @endif
                                @if($myCard->card_status=="terminated")
                                <div class="card-details">
                                    <a href="javascript:void(0)" class="deleteCardModal" data-id="{{ $myCard->id }}">
                                        <div class="details-icon">
                                            <i class="fas fa-trash"></i>
                                        </div>
                                        <h5 class="title">{{ __("Remove card") }}</h5>
                                    </a>
                                </div>
                                @endif
                                <div class="card-details">
                                    <a href="{{  setRoute('user.strowallet.virtual.card.transaction',$myCard->card_id) }}">
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
                            <p>{{ __("My Card")." (" }}{{ "0"."/".$cardApi->card_limit.")"}}</p>
                            <p>{{ __("No Virtual Card Created!") }}</p>
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

<div class="modal fade" id="unblockCardModal" tabindex="-1" aria-labelledby="unblockCard-modal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" id="unblockCard-modal">
                <h4 class="modal-title">{{__("Unlocking your card")}}</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="las la-times"></i></button>
            </div>
            <div class="modal-body">
                <h3>{{__("Information")}}s :</h3>
                <ol>
                    <li>{{__("will be charged from your wallet as a penalty to unblock your card",['amount'=>$cardApi->penality_price])}}</li>
                    <li>{{__("Please note that after unblocking, it is imperative to top up your card in order to make your payments. Without topping up, your card may be permanently deleted.")}}</li>
                </ol>
            </div>
            <form action="{{setRoute('user.strowallet.virtual.card.pay.penality')}}" method="POST">
                @csrf
                <input type="text" value="" name="card_id" hidden>
                <div class="modal-footer">
                    <button type="submit" id ="payPenality" class="btn btn--base w-100 btn-loading fund-btn">{{ __("Pay the penalty") }} : {{$cardApi->penality_price}} USD</button>
                </div>
             </form>
        </div>
    </div>
</div><div class="modal fade" id="removeCardModal" tabindex="-1" aria-labelledby="removeCard-modal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" id="removeCard-modal">
                <h4 class="modal-title">{{__("Remove card")}}</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="las la-times"></i></button>
            </div>
            <div class="modal-body">
                <h3>{{__("Information")}}s :</h3>
                <ol>
                    <li>{{__("This card is already deleted from the bank. Are you sure you want to permanently remove it from your account? This action is irreversible and all data associated with this card will be deleted from your PayOol™ space.")}}</li>
                </ol>
            </div>
            <form action="{{setRoute('user.strowallet.virtual.card.delete')}}" method="POST">
                @csrf
                <input type="text" value="" name="card_id" hidden>
                <div class="modal-footer">
                    <button type="submit" id ="deleteCard" class="btn btn--base w-100 btn-loading fund-btn">{{ __("Confirm Deletion") }}</button>
                </div>
             </form>
        </div>
    </div>
</div>
<div class="modal fade" id="withdrawCardModal" tabindex="-1" aria-labelledby="card-modal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" id="buycard-modal">
                <h4 class="modal-title">{{__(" Withdraw Money card")}}</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="las la-times"></i></button>
            </div>
            <div class="modal-body">

                    <form class="card-form row g-4" action="{{ route('user.strowallet.virtual.card.withdraw') }}" method="POST" id="myForm">
                        @csrf
                        <input type="hidden" name="id">
                    <div class="col-12">
                        <div class="row">
                            <div class="form-group">
                                <label>{{__("amount to withdraw")}}<span>*</span></label>
                                <input type="number" class="form--control" required placeholder="{{ __("Enter Amount") }}" name="fund_amount" value="{{ old("fund_amount") }}">
                                <div class="currency">
                                    <p>{{ get_default_currency_code() }}</p>
                                </div>
                                
                               <div class="d-flex justify-content-between">
                                <code class="d-block mt-3  text--base fw-bold balance-show fund-limit-show">--</code>
                                <code class="d-block mt-3  text--base fw-bold balance-show balance-amount"></code>
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
                                <h3 class="fs-6 fw-lighter py-1 text-capitalize">&bull; {{__("Total Withdraw")}} :
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
<div class="modal fade" id="StrowalletFundCardModal" tabindex="-1" aria-labelledby="card-modal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" id="buycard-modal">
                <h4 class="modal-title">{{__("Fund Card")}}</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="las la-times"></i></button>
            </div>
            <div class="modal-body">
                    <form class="card-form row g-4" action="{{ route('user.strowallet.virtual.card.fund') }}" method="POST">
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
@endsection
@push('script')
    <script>
        $(".active-deactive-btn").click(function(){
            var actionRoute =  "{{ setRoute('user.strowallet.virtual.card.make.default.or.remove') }}";
            var target = $(this).data('id');
            var btnText = $(this).text();
            var message     = `Are you sure to <strong>${btnText}</strong> this card?`;
            openAlertModal(actionRoute,target,message,btnText,"POST");
        });
        $(".payPenalityModal").on('click', function () {
                         //$("#api_appForm").submit();
                         console.log('ok ici')
                         
                         //console.log($("#api_method_app").val())
                         //var apiName =$("#api_method_app").val()
                         var modal =$('#unblockCardModal');
                         modal.find('input[name=card_id]').val($(this).data('id'));
                         $("#payPenality").click(function(){
                            //$("#api_appForm").submit()
                         })
                         console.log(modal)
                         //if(method!==apiName)
                         modal.modal('show')
        });
        $(".deleteCardModal").on('click', function () {
                         //$("#api_appForm").submit();
                         console.log('ok ici')
                         
                         //console.log($("#api_method_app").val())
                         //var apiName =$("#api_method_app").val()
                         var modal =$('#removeCardModal');
                         modal.find('input[name=card_id]').val($(this).data('id'));

                         console.log(modal)
                         //if(method!==apiName)
                         modal.modal('show')
        });
    </script>
    <script>
        $('.strowalletFundCard').on('click', function () {
            var modal = $('#StrowalletFundCardModal');
            modal.find('input[name=id]').val($(this).data('id'));
            $(document).ready(function(){
                getLimit();
                getFees();
                getPreview();
            });
            $('#StrowalletFundCardModal').find("input[name=fund_amount]").keyup(function(){
                getFees();
                getPreview();
            });
            $('#StrowalletFundCardModal').find("input[name=fund_amount]").focusout(function(){
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
                    $('#StrowalletFundCardModal').find('.fund-limit-show').html("{{ __('Limit') }} " + min_limit_calc + " " + currencyCode + " - " + max_limit_clac + " " + currencyCode);

                    return {
                        minLimit:min_limit_calc,
                        maxLimit:max_limit_clac,
                    };
                }else {
                    $('#StrowalletFundCardModal').find('.fund-limit-show').html("--");
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
                var sender_amount = $('#StrowalletFundCardModal').find("input[name=fund_amount]").val();
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
                $('#StrowalletFundCardModal').find(".fund-fees-show").html( parseFloat(charges.fixed).toFixed(2) + " " + currencyCode + " + " + parseFloat(charges.percent).toFixed(2) + "% = " + parseFloat(charges.total).toFixed(2) + " " + currencyCode);
            }
            function getPreview() {
                    var senderAmount = $('#StrowalletFundCardModal').find("input[name=fund_amount]").val();
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
                    $('#StrowalletFundCardModal').find('.fund-payable-total').html( pay_in_total + " " + sender_currency);

            }
            function enterLimit(){
                var min_limit = parseFloat("{{getAmount($cardReloadCharge->min_limit)}}");
                var max_limit =parseFloat("{{getAmount($cardReloadCharge->max_limit)}}");
                var currencyRate = acceptVar().currencyRate;
                var sender_amount = parseFloat($("input[name=fund_amount]").val());

                if( sender_amount < min_limit ){
                    throwMessage('error',["{{ __('Please follow the mimimum limit') }}"]);
                    $('#StrowalletFundCardModal').find('.fund-btn').attr('disabled',true)
                }else if(sender_amount > max_limit){
                    throwMessage('error',["{{ __('Please follow the maximum limit') }}"]);
                    $('#StrowalletFundCardModal').find('.fund-btn').attr('disabled',true)
                }else{
                    $('#StrowalletFundCardModal').find('.fund-btn').attr('disabled',false)
                }

            }
            modal.modal('show');
        });
        $('.withdrawCard').on('click', function () {
            var modal = $('#withdrawCardModal');
            modal.find('input[name=id]').val($(this).data('id'));
            var amount =$(this).data('amount');
            console.log(amount);
            var textAmount=modal.find('.balance-amount');
            console.log(textAmount)
            //console.log(acceptVar().currencyCode)
            //var texta=acceptVar().balanceName.''.amount.''.acceptVar().currencyCode
            textAmount.text(`${acceptVar().balanceName} ${amount} ${acceptVar().currencyCode}`)
            $(document).ready(function(){
                getLimitWithdraw(amount);
                getFeesWithdraw();
                getPreviewWithdraw();
            });
            $('#withdrawCardModal').find("input[name=fund_amount]").keyup(function(){
               getFeesWithdraw();
               getPreviewWithdraw();
            });
            $('#withdrawCardModal').find("input[name=fund_amount]").focusout(function(){
                enterLimitWithdraw(amount);
            });

            function getLimitWithdraw(amount) {
                var currencyCode = acceptVar().currencyCode;
                var currencyRate = acceptVar().currencyRate;

                if($.isNumeric(amount)) {
                    var min_limit_calc = 1;//parseFloat(min_limit/currencyRate).toFixed(2);
                    var max_limit_clac = parseFloat(amount/currencyRate).toFixed(2);
                    $('#withdrawCardModal').find('.fund-limit-show').html("{{ __('Limit') }} " + min_limit_calc + " " + currencyCode + " - " + max_limit_clac + " " + currencyCode);

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
            function getFeesWithdraw() {
                var currencyCode = acceptVar().currencyCode;
                var percent = acceptVar().currencyPercentCharge;
                var charges = feesCalculation();
                if (charges == false) {
                    return false;
                }
                $('#withdrawCardModal').find(".fund-fees-show").html( parseFloat(charges.fixed).toFixed(2) + " " + currencyCode + " + " + parseFloat(charges.percent).toFixed(2) + "% = " + parseFloat(charges.total).toFixed(2) + " " + currencyCode);
            }
            function feesCalculation() {
                var currencyCode = acceptVar().currencyCode;
                var currencyRate = acceptVar().currencyRate;
                var sender_amount = $('#withdrawCardModal').find("input[name=fund_amount]").val();
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
            function getPreviewWithdraw() {
                    var senderAmount = $('#withdrawCardModal').find("input[name=fund_amount]").val();
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
                    $('#withdrawCardModal').find('.fund-payable-total').html( pay_in_total + " " + sender_currency);

            }
            function enterLimitWithdraw(amount){
                var min_limit = parseFloat("{{getAmount($cardReloadCharge->min_limit)}}");
                var max_limit =parseFloat("{{getAmount($cardReloadCharge->max_limit)}}");
                var currencyRate = acceptVar().currencyRate;
                var sender_amount = parseFloat($('#withdrawCardModal').find("input[name=fund_amount]").val());
                console.log('amount : ',amount,"send_amount : ",sender_amount)
                if( sender_amount < min_limit ){
                    throwMessage('error',["{{ __('Please follow the mimimum limit') }}"]);
                    $('#withdrawCardModal').find('.fund-btn').attr('disabled',true)
                }else if(sender_amount > amount){
                    throwMessage('error',["{{ __('Please follow the maximum limit') }}"]);
                    $('#withdrawCardModal').find('.fund-btn').attr('disabled',true)
                }else{
                    $('#withdrawCardModal').find('.fund-btn').attr('disabled',false)
                }

            }
            modal.modal('show');
            function acceptVar() {

var currencyCode = defualCurrency;
var currencyRate = defualCurrencyRate;
var currencyMinAmount ="{{getAmount($cardWithdrawCharge->min_limit)}}";
var currencyMaxAmount = "{{getAmount($cardWithdrawCharge->max_limit)}}";
var currencyFixedCharge = "{{getAmount($cardWithdrawCharge->fixed_charge)}}";
var currencyPercentCharge = "{{getAmount($cardWithdrawCharge->percent_charge)}}";
var balanceName="{{ __(" Balance: ") }}"



return {
    currencyCode:currencyCode,
    currencyRate:currencyRate,
    currencyMinAmount:currencyMinAmount,
    currencyMaxAmount:currencyMaxAmount,
    currencyFixedCharge:currencyFixedCharge,
    currencyPercentCharge:currencyPercentCharge,
    balanceName:balanceName


};
}})
    </script>
@endpush
