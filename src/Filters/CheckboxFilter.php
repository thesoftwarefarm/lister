<?php


namespace TsfCorp\Lister\Filters;

/**
 * Class CheckboxFilter
 * @package TsfCorp\Lister\Filters
 */
class CheckboxFilter extends ListerFilter
{
    protected $type = self::TYPE_CHECKBOX;

    /** @var array */
    public $items;

    /**
     * @param array $items
     * @return CheckboxFilter
     */
    public function setItems(array $items): CheckboxFilter
    {
        $this->items = $items;
        return $this;
    }

    protected function viewData()
    {
        parent::viewData();

        $this->setViewData([
            'items' => $this->items
        ]);
    }

    /**
     * @inheritDoc
     */
    public function mandatoryProperties(): array
    {
        return [
            'input_name',
            'db_column',
            'search_operator',
            'items',
        ];
    }

    public function validate(): bool
    {
        if (!parent::validate()) {
            return false;
        }

        return in_array($this->getSearchKeyword(), array_keys($this->items));
    }
}
