<?php

namespace TsfCorp\Lister\Facades;


use Illuminate\Support\Facades\Facade;

/**
 * @method static \TsfCorp\Lister\Filters\TextfieldFilter textfield(string $input_name = "", string $label = "")
 * @method static \TsfCorp\Lister\Filters\SelectFilter select(string $input_name = "", string $label = "")
 * @method static \TsfCorp\Lister\Filters\RadioFilter radio(string $input_name = "", string $label = "")
 * @method static \TsfCorp\Lister\Filters\CheckboxFilter checkbox(string $input_name = "", string $label = "")
 * @method static \TsfCorp\Lister\Filters\RawFilter raw(string $raw_query = "")
 *
 * @see \TsfCorp\Lister\ListerFilterFactory
 */
class ListerFilter extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'listerfilter';
    }

}