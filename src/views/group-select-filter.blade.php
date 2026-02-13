<div class="mb-3">
    <label class="form-label" for="filter_{{ \Illuminate\Support\Str::kebab($input_name) }}">{{ $label }}</label>
    <select class="form-select filter_{{ \Illuminate\Support\Str::kebab($input_name) }}" id="filter_{{ \Illuminate\Support\Str::kebab($input_name) }}" name="{{ $input_name }}">
        <option></option>
        @foreach($items as $label => $group_items)
            <optgroup label="{{ $label }}">
                @foreach($group_items as $key => $item)
                    <option value="{{ $key }}" @if(!is_null($search_keyword) && $key == $search_keyword) selected @endif>{{ $item }}</option>
                @endforeach
            </optgroup>
        @endforeach
    </select>
</div>
