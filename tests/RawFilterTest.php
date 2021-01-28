<?php

namespace TsfCorp\Lister\Test;

use Exception;
use TsfCorp\Lister\Facades\ListerFilter;

class RawFilterTest extends TestBootstrap
{
    /** @test */
    function no_render()
    {
        $filter = ListerFilter::raw("query")
            ->setLabel("test")
            ->setSearchKeyword("hello")
            ->render();

        $this->assertEmpty($filter);
    }

    /**
     * @test
     */
    function it_throws_error_if_property_are_not_set()
    {
        $this->expectException(Exception::class);

        ListerFilter::raw("test")->validate();
    }

    /**
     * @test
     */
    function no_errors_if_render_is_false()
    {
        $result = ListerFilter::raw("test")->noRender()->validate();
        $this->assertTrue($result);
    }
}