<?php

namespace TsfCorp\Lister\Filters;

use Closure;
use Illuminate\Support\Str;
use Illuminate\View\View;

abstract class ListerFilter
{
    public const TYPE_INPUT = "input";
    public const TYPE_SELECT = "select";
    public const TYPE_GROUP_SELECT = "group-select";
    public const TYPE_CHECKBOX = "checkbox";
    public const TYPE_RADIO = "radio";
    public const TYPE_RAW = "raw";

    protected string $type;
    protected string $input_name;
    protected string $label = '';
    protected string $db_column = '';
    protected mixed $search_keyword = null;
    protected string $search_operator = '=';
    protected string $raw_query = '';
    protected string $default_raw_query = '';
    protected bool $is_active = false;
    protected string $view_name;
    protected array $view_data = [];
    protected bool $has_render = true;
    protected bool $render_input = true;
    protected bool $render_search_keyword = true;
    private ?Closure $search_keyword_callback = null;

    public function __construct()
    {
        $this->view_name = 'lister::' . Str::kebab(class_basename($this));
    }

    public static function textfield(string $input_name, string $label = '', string $db_column = '')
    {
        return TextfieldFilter::make($input_name, $label, $db_column);
    }

    public static function select(string $input_name, string $label = '', string $db_column = '')
    {
        return SelectFilter::make($input_name, $label, $db_column);
    }

    public static function groupSelect(string $input_name, string $label = '', string $db_column = '')
    {
        return GroupSelectFilter::make($input_name, $label, $db_column);
    }

    public static function radio(string $input_name, string $label = '', string $db_column = '')
    {
        return RadioFilter::make($input_name, $label, $db_column);
    }

    public static function checkbox(string $input_name, string $label = '', string $db_column = '')
    {
        return CheckboxFilter::make($input_name, $label, $db_column);
    }

    public function setInputName(string $input_name): static
    {
        $this->input_name = $input_name;

        return $this;
    }

    public function getInputName(): string
    {
        return $this->input_name;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setDbColumn(string $db_column): static
    {
        $this->db_column = $db_column;

        return $this;
    }

    public function getDbColumn(): string
    {
        return $this->db_column ?: $this->input_name;
    }

    public function setSearchOperator(string $search_operator): static
    {
        $this->search_operator = strtoupper($search_operator);

        return $this;
    }

    public function getSearchOperator(): string
    {
        return $this->search_operator;
    }

    public function getRawQuery(): string
    {
        return $this->raw_query;
    }

    public function setRawQuery(string|callable $raw_query): static
    {
        $this->raw_query = is_callable($raw_query) ? $raw_query() : $raw_query;

        return $this;
    }

    public function getDefaultRawQuery(): string
    {
        return $this->default_raw_query;
    }

    public function setDefaultRawQuery(string|callable $default_raw_query): static
    {
        $this->default_raw_query = is_callable($default_raw_query) ? $default_raw_query() : $default_raw_query;

        return $this;
    }

    public function setSearchKeywordCallback(Closure $callback)
    {
        $this->search_keyword_callback = $callback;

        return $this;
    }

    public function getSearchKeyword(): mixed
    {
        return $this->search_keyword;
    }

    public function setSearchKeyword(mixed $search_keyword): static
    {
        $this->search_keyword = $this->search_keyword_callback ? call_user_func($this->search_keyword_callback, $search_keyword) : $search_keyword;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function setActive(bool $is_active): static
    {
        $this->is_active = $is_active;

        return $this;
    }

    public function setViewName(string $view_name): static
    {
        $this->view_name = $view_name;

        return $this;
    }

    public function getViewName(): string
    {
        return $this->view_name;
    }

    public function view(): View
    {
        return view($this->getViewName());
    }

    protected function viewData(): void
    {
        $this->setViewData([
            'label' => $this->label,
            'input_name' => $this->input_name,
            'search_keyword' => $this->search_keyword,
        ]);
    }

    public function setViewData(array $data): static
    {
        $this->view_data = array_merge($this->view_data, $data);

        return $this;
    }

    public function noRender(): static
    {
        $this->has_render = false;

        return $this;
    }

    public function hasRender(): bool
    {
        return $this->has_render;
    }

    public function doNotRenderInput(): static
    {
        $this->render_input = false;

        return $this;
    }

    public function shouldRenderInput(): bool
    {
        return $this->render_input;
    }

    public function doNotRenderSearchKeyword(): static
    {
        $this->render_search_keyword = false;

        return $this;
    }

    public function shouldRenderSearchKeyword(): bool
    {
        return $this->render_search_keyword;
    }

    public function render(): string
    {
        $this->viewData();

        return $this->view()->with($this->view_data)->render();
    }
}
