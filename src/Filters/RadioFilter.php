<?php


namespace TsfCorp\Lister\Filters;

/**
 * Class RadioFilter
 * @package TsfCorp\Lister\Filters
 */
class RadioFilter extends ListerFilter
{
    protected $type = self::TYPE_RADIO;

    /** @var array */
    public $items;

    /**
     * @param array $items
     * @return RadioFilter
     */
    public function setItems(array $items): RadioFilter
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

    public function setSearchKeyword($search_keyword): ListerFilter
    {
        if (in_array($search_keyword, array_keys($this->items))) {
            $this->search_keyword = $search_keyword;
        }

        return $this;
    }
}
