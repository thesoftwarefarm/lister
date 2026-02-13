<?php

namespace TsfCorp\Lister\Filters;

use Illuminate\Support\Str;

class TextfieldFilter extends ListerFilter
{
    protected string $type = self::TYPE_INPUT;

    public static function make(string $input_name, string $label = '', string $db_column = '')
    {
        return (new static())
            ->setInputName($input_name)
            ->setLabel($label)
            ->setDbColumn($db_column)
            ->setViewName('lister::' . Str::kebab(class_basename(self::class)));
    }
}
