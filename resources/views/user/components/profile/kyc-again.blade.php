@if ($basic_settings->kyc_verification == true && isset($user_kyc) && $user_kyc != null && $user_kyc->fields != null)
    <h3 class="title">{{ __("KYC Information") }} &nbsp; <span class="{{ auth()->user()->kycStringStatus->class }}">{{__( auth()->user()->kycStringStatus->value) }}</span></h3>

        <ul class="kyc-data">
            @foreach (auth()->user()->kyc->data ?? [] as $item)
                <li>
                    @if ($item->type == "file")
                        @php
                            $file_link = get_file_link("kyc-files",$item->value);
                        @endphp
                        <span class="kyc-title">{{ $item->label }}:</span>
                        @if (its_image($item->value))
                            <div class="kyc-image">
                                <img src="{{ $file_link }}" alt="{{ $item->label }}">
                            </div>
                        @else
                            <span class="text--danger">
                                @php
                                    $file_info = get_file_basename_ext_from_link($file_link);
                                @endphp
                                <a href="{{ setRoute('file.download',["kyc-files",$item->value]) }}" >
                                    {{ Str::substr($file_info->base_name ?? "", 0 , 20 ) ."..." . $file_info->extension ?? "" }}
                                </a>
                            </span>
                        @endif
                    @else
                        <span class="kyc-title">{{ $item->label }}:</span>
                        <span>{{ $item->value }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
    <p>{{ __("Please submit your KYC information with valid data.") }}</p>
    <form action="{{ setRoute('user.authorize.kyc.submit') }}" class="account-form" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row ml-b-20">

            @include('user.components.generate-kyc-fields',['fields' => $kyc_fields])

            <div class="col-lg-12 form-group">
                <div class="forgot-item">
                    <label>{{ __("Back To") }} <a href="{{ setRoute('user.dashboard') }}" class="text--base"> {{ __("Dashboard") }}</a></label>
                </div>
            </div>
            <div class="col-lg-12 form-group text-center">
                <button type="submit" class="btn--base w-100">{{ __("Submit") }}</button>
            </div>
        </div>
    </form>

@endif
