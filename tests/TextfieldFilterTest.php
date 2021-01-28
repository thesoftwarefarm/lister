<?php

namespace TsfCorp\Lister\Test;

use Exception;
use TsfCorp\Lister\Facades\ListerFilter;

class TextfieldFilterTest extends TestBootstrap
{
    /** @test */
    function filter_is_rendered_properly()
    {
        $filter = ListerFilter::textfield("test-input", "test-label")->render();

        $this->assertStringContainsString('test-input', $filter);
        $this->assertStringContainsString('test-label', $filter);
        $this->assertStringContainsString('name="test-input"', $filter);
    }

    /**
     * @test
     *
     */
    function it_throws_error_if_property_are_not_set()
    {
        $this->expectException(Exception::class);

        ListerFilter::textfield()->validate();
    }

    /**
     * @test
     */
    function it_populates_searched_keyword()
    {
        $filter = ListerFilter::textfield("test-input", "test-label")->setSearchKeyword("testme");

        $this->assertStringContainsString('value="testme"', $filter->render());
    }
}