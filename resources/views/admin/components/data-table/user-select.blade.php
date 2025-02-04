<select class="form--control search_user_select"  id="select-country" data-live-search="true" name="user">
    @foreach($users as $user)
    <option  value="{{$user->id}}">{{$user->username}}</option>
    @endforeach

</select>
<!--<table class="custom-table user-search-table">
    <thead>
        <tr>
            <th></th>
            <th>{{ __("Fullname") }}</th>
            <th>{{ __("Email") }}</th>
            <th>{{ __("Email Verification") }}</th>
            <th>{{ __("Status") }}</th>
            <th>{{ __("Action") }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($users ?? [] as $key => $item)
            <tr>
                <td>
                    <ul class="user-list">
                        <li><img src="{{ $item->userImage }}" alt="user"></li>
                    </ul>
                </td>
                <td><span>{{ $item->fullname}}</span></td>
                <td>{{ $item->email }}</td>
                <td>
                    <span class="{{ $item->emailStatus->class }}">{{ __($item->emailStatus->value) }}</span>
                </td>
                <td>
                    @php
                    $datar=["admin.users.kyc.unverified","admin.users.kyc.verified","admin.users.kyc.pending","admin.users.kyc.rejected"];
                    $isKyc=in_array(Route::currentRouteName(),$datar);
                    //dd($isKyc);
                    @endphp
                    @if ($isKyc)
                        <span class="{{ $item->kycStringStatus->class }}">{{ __($item->kycStringStatus->value) }}</span>
                    @else
                        <span class="{{ $item->stringStatus->class }}">{{ __($item->stringStatus->value) }}</span>
                    @endif
                </td>
                <td>
                    @if ($isKyc)
                        @include('admin.components.link.info-default',[
                            'href'          => setRoute('admin.users.kyc.details', $item->username),
                            'permission'    => "admin.users.kyc.details",
                        ])
                    @else
                        @include('admin.components.link.info-default',[
                            'href'          => setRoute('admin.users.details', $item->username),
                            'permission'    => "admin.users.details",
                        ])
                    @endif
                </td>
            </tr>
        @empty
            @include('admin.components.alerts.empty',['colspan' => 7])
        @endforelse
    </tbody>
</table>-->
