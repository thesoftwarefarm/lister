<?php

namespace TsfCorp\Lister\Facades;


use Illuminate\Support\Facades\Facade;
use TsfCorp\Lister\ListerFilterFactory;

class ListerFilter extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return ListerFilterFactory::class;
    }

}