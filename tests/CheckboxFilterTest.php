<?php

namespace TsfCorp\Lister\Test;

use Exception;
use TsfCorp\Lister\Facades\ListerFilter;

class CheckboxFilterTest extends TestBootstrap
{
    /** @test */
    function filter_is_rendered_properly()
    {
        $filter = ListerFilter::checkbox("test-checkbox")
            ->setItems([
                'a' => "Option 1",
                'b' => "Option 2",
                'c' => "Option 3",
            ])
            ->render();

        $this->assertStringContainsString('test-checkbox[]', $filter);
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

        ListerFilter::checkbox("test-checkbox")->render();
    }

    /**
     * @test
     *
     */
    function searched_keywords_are_checked()
    {
        $filter = ListerFilter::checkbox("test-checkbox")
            ->setItems([
                'a' => "Option 1",
                'b' => "Option 2",
                'c' => "Option 3",
            ])
            ->setSearchKeyword(["a"]);

        $this->assertStringContainsString('checked', $filter->render());
    }
}