<?php

namespace TsfCorp\Lister\Tests;

use Exception;
use TsfCorp\Lister\Filters\ListerFilter;

class GroupSelectFilterTest extends TestCase
{
    function test_filter_is_rendered_properly()
    {
        $filter = ListerFilter::groupSelect('test-select', 'test-label-select')
            ->setItems([
                'Group name' => [
                    'a' => 'Option 1',
                    'b' => 'Option 2',
                    'c' => 'Option 3',
                ],
            ])
            ->render();

        $this->assertStringContainsString('test-select', $filter);
        $this->assertStringContainsString('test-label-select', $filter);
        $this->assertStringContainsString('Group name', $filter);
        $this->assertStringContainsString('Option 1', $filter);
        $this->assertStringContainsString('value="a"', $filter);
    }

    function test_filter_is_not_added_when_search_keyword_is_not_in_set()
    {
        $filter = ListerFilter::groupSelect('test-select')
            ->setItems([
               'Group name' => [
                    'a' => 'Option 1',
                    'b' => 'Option 2',
                    'c' => 'Option 3',
                ],
            ])
            ->setSearchKeyword('d');

        $this->assertEmpty($filter->getSearchKeyword());
    }
}
