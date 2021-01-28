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
    public function mandatoryProperties(): array
    {
        $props = [
            'raw_query',
        ];

        if ($this->has_render) {
            $props = array_merge($props, ['label', 'search_keyword']);
        }

        return $props;
    }

    public function render(): string
    {
        return "";
    }
}
