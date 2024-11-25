

<nav class="navbar-wrapper">
    <div class="dashboard-title-part">
        <div class="left">
            <div class="icon">
                <button class="sidebar-menu-bar">
                    <i class="fas fa-exchange-alt"></i>
                </button>
            </div>

            @yield('breadcrumb')
        </div>
        <div class="right">
            <div class="d-flex flex-column flex-md-row">
                @php
                $currentsApi=getCurrentsApi();
                @endphp
                <form class="card-form col-md-6 p-2 p-md-0  " action="{{ setRoute('user.change.api') }}" method="POST" id='api_appForm'>
                    @csrf
                    @method("POST")
                    <select class="js-select2 form--control nice-select" name="api_method_app" id="api_method_app">
                        <option disabled>{{ __("Select Platfrom") }}</option>
        
                        @foreach ($currentsApi as $item)
                        @if($item->is_active)<option value="{{$item->name}}" @if(getCurrentApi() == $item->name) selected @endif>@if($item->name=="soleaspay")
                            {{__("Carte Basique")}}
                            @elseif($item->name=="eversend")
                             {{__("Carte Premium")}}
                            @else
                            {{$item->name}}
                            @endif
                        </option>
                        @endif
                    @endforeach
                    </select>
                </form>
                <div class="modal fade" id="changeApiModal" tabindex="-1" aria-labelledby="changeApi-modal" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header" id="buycard-modal">
                                <h4 class="modal-title">{{__("change card type")}}</h4>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="las la-times"></i></button>
                            </div>
                            <div class="modal-body">
                                <h3>{{__("This shift implies that")}} :</h3>
                                <ol>
                                    <li>{{__("Previous cards will no longer be accessible unless you revert to the other type of card")}}</li>
                                    <li>{{__("When you switch from one card type to another, only cards matching the selected type will be visible in your account")}}</li>
                                </ol>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" id ="changeApi" class="btn btn--base w-100 btn-loading fund-btn">{{ __("Confirm") }}</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                @push('script')
                <script>
                    (function ($) {
                        "use strict";
                        var method = '{{ getCurrentApi()}}';
                        if (!method) {
                            method = 'flutterwave';
                        }
                        //$("#submitButton").click(function() { $("#api_appForm").submit(); });
                        $("#api_method_app").change(function() {
                         //$("#api_appForm").submit();
                         console.log($("#api_method_app").val())
                         var apiName =$("#api_method_app").val()
                         var modal =$('#changeApiModal');
                         $("#changeApi").click(function(){
                            $("#api_appForm").submit()
                         })
                         console.log(modal)
                         if(method!==apiName)
                         modal.modal('show')
                          });
            
                    })(jQuery);
            
                </script>
            @endpush
                <div class="">
                    @php
                        $session_lan = session('local')??get_default_language_code();
                    @endphp
                    <select class="language-select langSel">
                        @foreach($__languages as $item)
                            <option value="{{$item->code}}" @if( $session_lan == $item->code) selected  @endif>{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="header-notification-wrapper">

                <button class="notification-icon">
                    <i class="las la-bell"></i>
                </button>
                <div class="notification-wrapper">
                    <div class="notification-header">
                        <h5 class="title">{{ __("notification") }}</h5>
                    </div>
                    <ul class="notification-list">
                        @foreach (get_user_notifications() ?? [] as $item)
                        <li>
                            <div class="thumb">
                                <img src="{{ auth()->user()->userImage }}" alt="user" />
                            </div>
                            <div class="content">
                                <div class="title-area">
                                    <h5 class="title">{{ __($item->message->title) }}</h5>
                                    <span class="time">{{ $item->created_at->diffForHumans() }}</span>
                                </div>
                                <span class="sub-title">{{ $item->message->message ?? "" }}</span>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <div class="header-user-wrapper">
                <div class="header-user-thumb">
                    <a href="{{ setRoute('user.profile.index') }}"><img src="{{ auth()->user()->userImage }}" alt="client" /></a>
                </div>
            </div>
        </div>
    </div>
</nav>


