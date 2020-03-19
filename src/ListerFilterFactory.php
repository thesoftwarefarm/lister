<?php


namespace TsfCorp\Lister;


use ErrorException;
use InvalidArgumentException;
use TsfCorp\Lister\Filters\CheckboxFilter;
use TsfCorp\Lister\Filters\TextfieldFilter;
use TsfCorp\Lister\Filters\ListerFilter;
use TsfCorp\Lister\Filters\RadioFilter;
use TsfCorp\Lister\Filters\RawFilter;
use TsfCorp\Lister\Filters\SelectFilter;

/**
 * Class ListerFilterFactory
 * @package TsfCorp\Lister
 *
 * @method static TextfieldFilter input()
 * @method static SelectFilter select()
 * @method static RadioFilter radio()
 * @method static CheckboxFilter checkbox()
 * @method static RawFilter raw()
 */
class ListerFilterFactory
{
    /**
     *
     * @param string $type
     * @param array $arguments
     * @return mixed
     *
     * @throws ErrorException
     */
    public function provide(string $type, array $arguments)
    {
        $class = "TsfCorp\Lister\Filters\\" . ucfirst($type) . "Filter";

        if (!class_exists($class)) {
            throw new ErrorException("Required type of filter: $type doesn't exist.");
        }

        /** @var ListerFilter $filter */
        $filter = (new $class);

        if (isset($arguments[0]) && strlen($arguments[0]) > 0) {
            if ($type == "raw") {
                $filter->setRawQuery($arguments[0]);
            } else {
                $filter->setInputName($arguments[0]);
            }
        }

        if (isset($arguments[1]) && strlen($arguments[1]) > 0) {
            $filter->setLabel($arguments[1]);
        }

        return $filter;
    }

    public function __call($name, $arguments)
    {
        if (method_exists($this, $name)) {
            return $this->{$name}($arguments);
        } else {
            return $this->provide($name, $arguments);
        }
    }
}