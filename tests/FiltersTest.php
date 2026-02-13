<?php

namespace TsfCorp\Lister\Tests;

use TsfCorp\Lister\Filters\ListerFilter;

class FiltersTest extends TestCase
{
    function test_it_chooses_a_default_view_name_based_on_the_class()
    {
        $filter = ListerFilter::textfield("input-name", "input-lable");

        $this->assertEquals('lister::textfield-filter', $filter->getViewName());
    }

    function test_properties_are_available_to_the_view()
    {
        $rendered = ListerFilter::textfield("input-name", "input-label")->render();

        $this->assertStringContainsString('input-label', $rendered);
        $this->assertStringContainsString('input-name', $rendered);
    }
}
