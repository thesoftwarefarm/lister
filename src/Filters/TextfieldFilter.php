<?php

namespace TsfCorp\Lister\Filters;

/**
 * Class TextfieldFilter
 * @package TsfCorp\Lister\Filters
 */
class TextfieldFilter extends ListerFilter
{
    protected $type = self::TYPE_INPUT;

    /**
     * @inheritDoc
     */
    public function mandatoryProperties(): array
    {
        return [
            'label',
            'input_name',
            'db_column',
            'search_operator',
        ];
    }
}
