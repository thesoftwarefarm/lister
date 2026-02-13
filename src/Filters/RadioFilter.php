<?php

namespace TsfCorp\Lister\Filters;

use Illuminate\Support\Str;

class RadioFilter extends ListerFilter
{
    protected string $type = self::TYPE_RADIO;
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
        if (in_array($search_keyword, array_keys($this->items))) {
            $this->search_keyword = $search_keyword;
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
