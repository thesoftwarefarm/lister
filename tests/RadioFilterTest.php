<?php

namespace TsfCorp\Lister\Test;

use Exception;
use TsfCorp\Lister\Facades\ListerFilter;

class RadioFilterTest extends TestBootstrap
{
    /** @test */
    function filter_is_rendered_properly()
    {
        $filter = ListerFilter::radio("test-radio")
            ->setItems([
                'a' => "Option 1",
                'b' => "Option 2",
                'c' => "Option 3",
            ])
            ->render();

        $this->assertStringContainsString('test-radio', $filter);
        $this->assertStringContainsString('Option 1', $filter);
        $this->assertStringContainsString('value="a"', $filter);
    }

    /**
     * @test
     *
     */
    function it_throws_error_if_items_are_not_set()
    {
        $this->expectException(Exception::class);

        ListerFilter::radio("test-radio")
            ->setDbColumn("aaa")
            ->validate();
    }
}