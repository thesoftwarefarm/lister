<div class="form-group">
    @if (isset($label) && !empty($label))
    <label>{{ $label }}</label>
    @endif

    <div>
    @foreach($items as $key => $item)
        <div class="custom-control custom-control-inline custom-checkbox">
            <input type="checkbox" class="custom-control-input filter_{{ \Illuminate\Support\Str::kebab($input_name) }}" id="checkbox_{{ \Illuminate\Support\Str::kebab($input_name) }}_{{ $key }}" name="{{ $input_name }}[]" value="{{ $key }}" @if(is_array($search_keyword) && in_array($key, $search_keyword)) checked @endif>
            <label class="custom-control-label" for="checkbox_{{ \Illuminate\Support\Str::kebab($input_name) }}_{{ $key }}">{{ $item }}</label>
        </div>
    @endforeach
    </div>
</div>
