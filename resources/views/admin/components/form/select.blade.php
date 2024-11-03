@if (isset($label))
    @php
        $for_id = preg_replace('/[^A-Za-z0-9\-]/', '', Str::lower($label));
    @endphp
    <label for="{{ $for_id ?? "" }}">{{ $label }}</label>
@endif
<select id="{{ $for_id ?? "" }}" name="{{ $name ?? "" }}" class="form-select {{ $class ?? "" }}"  @if ($multiple) multiple @endif {{ $attribute ?? "" }}>
@if (isset($options))

        @foreach ($options as $item => $input_value)
           
                <option value="{{ $input_value ?? "" }}" 
                    @if (isset($value) && $value == $input_value)
                        {{ "selected" }}
                    @endif
                >
                {{ $item }}
                </option>
            </div>
        @endforeach
    @endif
</select>