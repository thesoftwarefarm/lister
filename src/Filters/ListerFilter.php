<?php


namespace TsfCorp\Lister\Filters;


use Exception;
use Illuminate\Support\Str;

/**
 * Class ListerFilter
 * @package TsfCorp\Lister\Filters
 */
abstract class ListerFilter
{
    const TYPE_INPUT = "input";
    const TYPE_SELECT = "select";
    const TYPE_CHECKBOX = "checkbox";
    const TYPE_RADIO = "radio";
    const TYPE_RAW = "raw";

    /**
     *  What type of filter this is: INPUT / SELECT / CHECKBOX
     * @var string
     */
    protected $type;

    /**
     * Label to be used in html view
     * @var string
     */
    private $label;

    /**
     * Name attribute of the html component
     * @var string
     */
    private $input_name;

    /**
     * Database column for where clause
     * @var string
     */
    private $db_column;

    /**
     * Where operator to be applied: = / <= / >= / <> / LIKE
     * default value is =
     *
     * @var string
     */
    private $search_operator = "=";

    /**
     * Keyword to search for
     * @var string|array
     */
    private $search_keyword;

    /**
     * Keyword to search for
     * @var string|array
     */
    private $raw_query;

    /**
     * If this filter is applied in listing
     * @var bool
     */
    private $is_active = false;

    /**
     * View to be used for render
     * @var string
     */
    private $view_name;

    /**
     * Data passed to the view
     * @var array
     */
    protected $view_data = [];

    /**
     * Specify if this filter can be rendered
     *
     * @var bool
     */
    protected $has_render = true;

    /**
     * ListerFilter constructor.
     */
    public function __construct()
    {
        $this->view_name = 'lister::' . Str::kebab(class_basename($this));
    }

    /**
     * @param mixed $input_name
     * @return ListerFilter
     */
    public function setInputName($input_name)
    {
        $this->input_name = $input_name;

        // default db column name to input name
        if (empty($this->db_column)) {
            $this->db_column = $this->input_name;
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getInputName()
    {
        return $this->input_name;
    }

    /**
     * @param mixed $label
     * @return ListerFilter
     */
    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param mixed $db_column
     * @return ListerFilter
     */
    public function setDbColumn($db_column)
    {
        $this->db_column = $db_column;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDbColumn()
    {
        return $this->db_column;
    }

    /**
     * @param mixed $search_operator
     * @return ListerFilter
     */
    public function setSearchOperator($search_operator)
    {
        $this->search_operator = strtoupper($search_operator);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSearchOperator()
    {
        return $this->search_operator;
    }

    /**
     * @return string
     */
    public function getRawQuery()
    {
        return $this->raw_query;
    }

    /**
     * @param string $raw_query
     * @return ListerFilter
     */
    public function setRawQuery(string $raw_query): ListerFilter
    {
        $this->raw_query = $raw_query;
        return $this;
    }

    /**
     * @return array|string
     */
    public function getSearchKeyword()
    {
        return $this->search_keyword;
    }

    /**
     * @param array|string $search_keyword
     * @return ListerFilter
     */
    public function setSearchKeyword($search_keyword): ListerFilter
    {
        $this->search_keyword = $search_keyword;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * @param bool $is_active
     */
    public function setActive(bool $is_active): void
    {
        $this->is_active = $is_active;
    }

    /**
     * @param string $view_name
     * @return ListerFilter
     */
    public function setViewName(string $view_name): ListerFilter
    {
        $this->view_name = $view_name;
        return $this;
    }

    /**
     * @return string
     */
    public function getViewName(): string
    {
        return $this->view_name;
    }

    /**
     * Load the view for the widget.
     *
     * @return \Illuminate\View\View
     */
    public function view()
    {
        return view($this->getViewName());
    }

    /**
     * Build the view data.
     */
    protected function viewData()
    {
        $this->setViewData([
            'label' => $this->label,
            'input_name' => $this->input_name,
            'search_keyword' => $this->search_keyword,
        ]);
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setViewData(array $data): ListerFilter
    {
        $this->view_data = array_merge($this->view_data, $data);
        return $this;
    }

    /**
     *
     */
    public function noRender(): ListerFilter
    {
        $this->has_render = false;
        return $this;
    }

    /**
     * Specify which class members are required
     *
     * @return array
     */
    public abstract function mandatoryProperties(): array;

    /**
     * @return bool
     * @throws Exception
     */
    public function validate(): bool
    {
        foreach ($this->mandatoryProperties() as $property) {
            if (!isset($this->{$property})) {
                throw new Exception(sprintf("Property %s must be set for this filter to work.", $property));
            }

            if (!is_array($this->{$property}) && empty($this->{$property})) {
                throw new Exception(sprintf("Property %s must have a value set for this filter to work.", $property));
            }
        }

        return true;
    }

    /**
     * @return array|string
     * @throws \Throwable
     */
    public function render()
    {
        $this->viewData();

        return $this->view()->with($this->view_data)->render();
    }
}
