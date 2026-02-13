<?php

namespace TsfCorp\Lister\Filters;

use Illuminate\Support\Str;

class SelectFilter extends ListerFilter
{
    protected string $type = self::TYPE_SELECT;
    public array $items = [];

    public static function make(string $input_name, string $label = '', string $db_column = '')
    {
        return (new static())
            ->setInputName($input_name)
            ->setLabel($label)
            ->setDbColumn($db_column)
            ->setViewName('lister::' . Str::kebab(class_basename(self::class)));
    }

    public function setItems(array $items): static
    {
        $this->items = $items;

        return $this;
    }

    protected function viewData(): void
    {
        parent::viewData();

        $this->setViewData([
            'items' => $this->items,
        ]);
    }

    public function setSearchKeyword(mixed $search_keyword): static
    {
        if (in_array($search_keyword, array_keys($this->items))) {
            $this->search_keyword = $search_keyword;
        }

        return $this;
    }
}
