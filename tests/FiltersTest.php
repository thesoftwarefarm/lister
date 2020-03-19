<?php

namespace TsfCorp\Lister\Test;

use TsfCorp\Lister\Facades\ListerFilter;

class FiltersTest extends TestBootstrap
{
    /** @test */
    function it_chooses_a_default_view_name_based_on_the_class()
    {
        $filter = ListerFilter::textfield("input-name", "input-lable");

        $this->assertEquals('lister::textfield-filter', $filter->getViewName());
    }

    /** @test */
    function properties_are_available_to_the_view()
    {
        $rendered = ListerFilter::textfield("input-name", "input-label")->render();

        $this->assertStringContainsString('input-label', $rendered);
        $this->assertStringContainsString('input-name', $rendered);
    }

    /**
     * @test
     */
    function it_renders_custom_view()
    {
        $filter = ListerFilter::textfield("test-input", "test-label")
            ->setViewName("custom-filter")
            ->setViewData([
                'custom_title' => "My custom title"
            ]);

        $this->assertStringContainsString('My custom title', $filter->render());
    }
}