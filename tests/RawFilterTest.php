<?php

namespace TsfCorp\Lister\Tests;

use Exception;
use TsfCorp\Lister\Facades\ListerFilter;

class RawFilterTest extends TestCase
{
    function test_no_render()
    {
        $filter = ListerFilter::raw("query")
            ->setLabel("test")
            ->setSearchKeyword("hello")
            ->render();

        $this->assertEmpty($filter);
    }

    function test_it_throws_error_if_property_are_not_set()
    {
        $this->expectException(Exception::class);

        ListerFilter::raw("test")->validate();
    }

    function test_no_errors_if_render_is_false()
    {
        $result = ListerFilter::raw("test")->noRender()->validate();
        $this->assertTrue($result);
    }
}
