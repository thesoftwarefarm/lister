<div class="form-group">
    <label for="filter_{{ \Illuminate\Support\Str::kebab($input_name) }}">{{ $label }}</label>
    <input type="text" class="form-control filter_{{ \Illuminate\Support\Str::kebab($input_name) }}" id="filter_{{ \Illuminate\Support\Str::kebab($input_name) }}" name="{{ $input_name }}" value="{{ $search_keyword }}">
</div>
