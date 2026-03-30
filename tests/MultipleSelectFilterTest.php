<?php

namespace TsfCorp\Lister\Tests;

use TsfCorp\Lister\Filters\ListerFilter;

class MultipleSelectFilterTest extends TestCase
{
    function test_filter_is_rendered_properly()
    {
        $filter = ListerFilter::multipleSelect('test-multiple')
            ->setItems([
                'a' => 'Option 1',
                'b' => 'Option 2',
                'c' => 'Option 3',
            ])
            ->render();

        $this->assertStringContainsString('test-multiple[]', $filter);
        $this->assertStringContainsString('Option 1', $filter);
        $this->assertStringContainsString('value="a"', $filter);
    }

    function test_searched_keywords_are_checked()
    {
        $filter = ListerFilter::checkbox('test-multiple')
            ->setItems([
                'a' => 'Option 1',
                'b' => 'Option 2',
                'c' => 'Option 3',
            ])
            ->setSearchKeyword(['a']);

        $this->assertStringContainsString('checked', $filter->render());
    }

    function test_invalid_searched_keywords_are_removed()
    {
        $filter = ListerFilter::checkbox('test-multiple')
            ->setItems([
                'a' => 'Option 1',
                'b' => 'Option 2',
                'c' => 'Option 3',
            ])
            ->setSearchKeyword(['a', 'd', 'e']);

        $this->assertArrayNotHasKey('d', $filter->getSearchKeyword());
        $this->assertArrayNotHasKey('e', $filter->getSearchKeyword());
    }
}
