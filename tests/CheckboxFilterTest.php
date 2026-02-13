<?php

namespace TsfCorp\Lister\Tests;

use Exception;
use TsfCorp\Lister\Facades\ListerFilter;

class CheckboxFilterTest extends TestCase
{
    function test_filter_is_rendered_properly()
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

    function test_searched_keywords_are_checked()
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

    function test_invalid_searched_keywords_are_removed()
    {
        $filter = ListerFilter::checkbox("test-checkbox")
            ->setItems([
                'a' => "Option 1",
                'b' => "Option 2",
                'c' => "Option 3",
            ])
            ->setSearchKeyword(['a', 'd', 'e']);

        $this->assertArrayNotHasKey('d', $filter->getSearchKeyword());
        $this->assertArrayNotHasKey('e', $filter->getSearchKeyword());
    }
}
