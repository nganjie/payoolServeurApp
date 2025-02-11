<table class="custom-table user-search-table">
    <thead>
        <tr>
            <th>{{ __("channel") }}</th>
            <th>{{ __("fee") }}</th>
            <th>{{ __("Card Id") }}</th>
            <th>{{ __("card transaction id") }}</th>
            <th>{{ __("reason") }}</th>
            <th>{{ __("created At") }}</th>
            <th>{{ __("Action") }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($users as $item)
            <tr>
                <td>
                    @php
                    //dd($item);
                    @endphp
                    {{$item['channel']}}
                </td>
                <td><span>{{ $item['fee']}}</span></td>
                <td>{{ $item['card_id'] }}</td>
                <td>
                    {{$item['card_transaction_id']}}
                </td>
                <td>
                    {{$item['reason']}}
                </td>
                <td>
                    @php
                    $date=date($item['created_at']);
                    @endphp
                    {{$date}}
                </td>
                <td>
                    @include('admin.components.link.info-default',[
                            'href'          => setRoute('admin.users.details', $item['username']),
                            'permission'    => "admin.users.details",
                        ])
                </td>
            </tr>
        @empty
            @include('admin.components.alerts.empty',['colspan' => 7])
        @endforelse
    </tbody>
</table>
