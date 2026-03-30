<div class="mb-3">
    <label class="form-label" for="filter_{{ \Illuminate\Support\Str::kebab($input_name) }}">{{ $label }}</label>
    <select class="form-select filter_{{ \Illuminate\Support\Str::kebab($input_name) }}" id="filter_{{ \Illuminate\Support\Str::kebab($input_name) }}" name="{{ $input_name }}[]" multiple="multiple">
        @foreach($items as $key => $item)
            <option value="{{ $key }}" @if(is_array($search_keyword) && in_array($key, $search_keyword)) selected @endif>{{ $item }}</option>
        @endforeach
    </select>
</div>
