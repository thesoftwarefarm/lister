<div class="mb-3">
    @if (isset($label) && !empty($label))
        <label class="form-label">{{ $label }}</label>
    @endif

    <div>
        @foreach($items as $key => $item)
            <div class="form-check">
                <input type="checkbox" class="form-check-input filter_{{ \Illuminate\Support\Str::kebab($input_name) }}" id="checkbox_{{ \Illuminate\Support\Str::kebab($input_name) }}_{{ $key }}" name="{{ $input_name }}[]" value="{{ $key }}" @if(is_array($search_keyword) && in_array($key, $search_keyword)) checked @endif>
                <label class="form-check-label" for="checkbox_{{ \Illuminate\Support\Str::kebab($input_name) }}_{{ $key }}">{{ $item }}</label>
            </div>
        @endforeach
    </div>
</div>
