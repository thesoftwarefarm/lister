<?php

namespace TsfCorp\Lister\Test;

use Exception;
use TsfCorp\Lister\Facades\ListerFilter;

class SelectFilterTest extends TestBootstrap
{
    /** @test */
    function filter_is_rendered_properly()
    {
        $filter = ListerFilter::select()
            ->setInputName("test-select")
            ->setLabel("test-label-select")
            ->setDbColumn("aaa")
            ->setSearchOperator("=")
            ->setItems([
                'a' => "Option 1",
                'b' => "Option 2",
                'c' => "Option 3",
            ])
            ->render();

        $this->assertStringContainsString('test-select', $filter);
        $this->assertStringContainsString('test-label-select', $filter);
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

        ListerFilter::select()
            ->setInputName("test-select")
            ->setDbColumn("aaa")
            ->setSearchOperator("=")
            ->render();
    }

    /**
     * @test
     *
     */
    function it_works_with_empty_array_for_items()
    {
        $this->expectException(Exception::class);

        ListerFilter::select()
            ->setInputName("test-select")
            ->setDbColumn("aaa")
            ->setSearchOperator("=")
            ->setItems([])
            ->render();
    }
}