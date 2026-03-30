<?php

namespace TsfCorp\Lister\Filters;

use Closure;

abstract class ListerFilter
{
    public const TYPE_INPUT = "input";
    public const TYPE_SIMPLE_SELECT = "simple-select";
    public const TYPE_MULTIPLE_SELECT = "multiple-select";
    public const TYPE_GROUP_SELECT = "group-select";
    public const TYPE_CHECKBOX = "checkbox";
    public const TYPE_RADIO = "radio";
    public const TYPE_RAW = "raw";

    protected string $type;
    protected string $input_name = '';
    protected string $label = '';
    protected string $db_column = '';
    protected mixed $search_keyword = null;
    protected string $search_operator = '=';
    protected string $raw_query = '';
    protected string $default_raw_query = '';
    protected string $view_name = '';
    protected bool $is_active = false;
    protected bool $render_input = true;
    private ?Closure $search_keyword_callback = null;

    public static function textfield(string $input_name, string $label = '', string $db_column = '')
    {
        return TextfieldFilter::make($input_name, $label, $db_column);
    }

    /**
     * @deprecated Use simpleSelect()
     */
    public static function select(string $input_name, string $label = '', string $db_column = '')
    {
        return self::simpleSelect($input_name, $label, $db_column);
    }

    public static function simpleSelect(string $input_name, string $label = '', string $db_column = '')
    {
        return SimpleSelectFilter::make($input_name, $label, $db_column);
    }

    public static function multipleSelect(string $input_name, string $label = '', string $db_column = '')
    {
        return MultipleSelectFilter::make($input_name, $label, $db_column);
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

    public function getType(): string
    {
        return $this->type;
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

    public function setSearchKeyword(mixed $search_keyword): static
    {
        $this->search_keyword = $this->search_keyword_callback ? call_user_func($this->search_keyword_callback, $search_keyword) : $search_keyword;

        return $this;
    }

    public function getSearchKeyword(): mixed
    {
        return $this->search_keyword;
    }

    public function setSearchKeywordCallback(Closure $callback)
    {
        $this->search_keyword_callback = $callback;

        return $this;
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

    public function setRawQuery(string|callable $raw_query): static
    {
        $this->raw_query = is_callable($raw_query) ? $raw_query() : $raw_query;

        return $this;
    }

    public function getRawQuery(): string
    {
        return $this->raw_query;
    }

    public function setDefaultRawQuery(string|callable $default_raw_query): static
    {
        $this->default_raw_query = is_callable($default_raw_query) ? $default_raw_query() : $default_raw_query;

        return $this;
    }

    public function getDefaultRawQuery(): string
    {
        return $this->default_raw_query;
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

    public function setActive(bool $is_active): static
    {
        $this->is_active = $is_active;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->is_active;
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

    protected function getViewData(): array
    {
        return [
            'label' => $this->label,
            'input_name' => $this->input_name,
            'search_keyword' => $this->search_keyword,
        ];
    }

    public function render(): string
    {
        return view($this->getViewName(), $this->getViewData())->render();
    }
}
