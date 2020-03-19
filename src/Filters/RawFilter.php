<?php

namespace TsfCorp\Lister\Filters;

/**
 * Class RawFilter
 * @package TsfCorp\Lister\Filters
 */
class RawFilter extends ListerFilter
{
    protected $type = self::TYPE_RAW;

    /**
     * @inheritDoc
     */
    public function validate()
    {
        return [
            'raw_query'
        ];
    }
}
