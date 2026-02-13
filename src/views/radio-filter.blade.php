<div class="mb-3">
    @if (isset($label) && !empty($label))
        <label class="form-label">{{ $label }}</label>
    @endif

    <div>
        @foreach($items as $key => $item)
            <div class="form-check">
                <input type="radio" class="form-check-input filter_{{ \Illuminate\Support\Str::kebab($input_name) }}" name="{{ $input_name }}" id="radio_{{ \Illuminate\Support\Str::kebab($input_name) }}_{{ $key }}" value="{{ $key }}" @if(!is_null($search_keyword) && $key == $search_keyword) checked @endif>
                <label class="form-check-label" for="radio_{{ \Illuminate\Support\Str::kebab($input_name) }}_{{ $key }}">{{ $item }}</label>
            </div>
        @endforeach
    </div>
</div>
