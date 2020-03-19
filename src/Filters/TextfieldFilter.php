<?php

namespace TsfCorp\Lister\Filters;


class TextfieldFilter extends ListerFilter
{
    protected $type = self::TYPE_INPUT;

    /**
     * @inheritDoc
     */
    public function validate()
    {
        return [
            'label',
            'input_name',
            'db_column',
            'search_operator',
        ];
    }
}
