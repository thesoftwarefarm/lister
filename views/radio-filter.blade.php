<div class="form-group">
    @if (isset($label) && !empty($label))
        <label>{{ $label }}</label>
    @endif

    <div>
        @foreach($items as $key => $item)
            <div class="custom-control custom-control-inline custom-radio">
                <input type="radio" class="custom-control-input filter_{{ \Illuminate\Support\Str::kebab($input_name) }}" name="{{ $input_name }}" id="radio_{{ \Illuminate\Support\Str::kebab($input_name) }}_{{ $key }}" value="{{ $key }}" @if($key == $search_keyword) checked @endif>
                <label class="custom-control-label" for="radio_{{ \Illuminate\Support\Str::kebab($input_name) }}_{{ $key }}">{{ $item }}</label>
            </div>
        @endforeach
    </div>
</div>
