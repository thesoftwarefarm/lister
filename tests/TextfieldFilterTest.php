<?php

namespace TsfCorp\Lister\Tests;

use Exception;
use TsfCorp\Lister\Filters\ListerFilter;

class TextfieldFilterTest extends TestCase
{
    function test_filter_is_rendered_properly()
    {
        $filter = ListerFilter::textfield('test-input', 'test-label')->render();

        $this->assertStringContainsString('test-input', $filter);
        $this->assertStringContainsString('test-label', $filter);
        $this->assertStringContainsString('name="test-input"', $filter);
    }

    function test_it_populates_searched_keyword()
    {
        $filter = ListerFilter::textfield('test-input', 'test-label')->setSearchKeyword('testme');

        $this->assertStringContainsString('value="testme"', $filter->render());
    }
}
