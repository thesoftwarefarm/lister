<?php

namespace TsfCorp\Lister\Tests;

use Exception;
use TsfCorp\Lister\Filters\ListerFilter;

class RadioFilterTest extends TestCase
{
    function test_filter_is_rendered_properly()
    {
        $filter = ListerFilter::radio('test-radio', 'test-label-radio')
            ->setItems([
                'a' => 'Option 1',
                'b' => 'Option 2',
                'c' => 'Option 3',
            ])
            ->render();

        $this->assertStringContainsString('test-radio', $filter);
        $this->assertStringContainsString('test-label-radio', $filter);
        $this->assertStringContainsString('Option 1', $filter);
        $this->assertStringContainsString('value="a"', $filter);
    }

    function test_filter_is_not_added_when_search_keyword_is_not_in_set()
    {
        $filter = ListerFilter::radio('test-radio')
            ->setItems([
                'a' => 'Option 1',
                'b' => 'Option 2',
                'c' => 'Option 3',
            ])
            ->setSearchKeyword('d');

        $this->assertEmpty($filter->getSearchKeyword());
    }
}
