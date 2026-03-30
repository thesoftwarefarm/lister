<?php

namespace TsfCorp\Lister\Filters;

use Illuminate\Support\Str;

class MultipleSelectFilter extends ListerFilter
{
    protected string $type = self::TYPE_MULTIPLE_SELECT;
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

    public function setSearchKeyword(mixed $search_keyword): static
    {
        if (is_array($search_keyword)) {
            $this->search_keyword = array_intersect($search_keyword, array_keys($this->items));
        }

        return $this;
    }

    protected function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            'items' => $this->items,
        ]);
    }
}
