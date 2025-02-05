<?php

namespace Salla\ZATCA\Test\Unit;

use PHPUnit\Framework\TestCase;
use Salla\ZATCA\Tag;

class TagTest extends TestCase
{
    /** @test */
    public function it_can_create_tag_with_value()
    {
        $tag = new Tag(1, 'Test Value');
        
        $this->assertEquals(1, $tag->getTag());
        $this->assertEquals('Test Value', $tag->getValue());
        $this->assertEquals(strlen('Test Value'), $tag->getLength());
    }

    /** @test */
    public function it_converts_to_string_correctly()
    {
        $tag = new Tag(1, 'ABC');
        
        // The tag should output binary data for tag (01), length (03), and value (ABC)
        $expected = pack('H*', '01') . pack('H*', '03') . 'ABC';
        $this->assertEquals($expected, (string) $tag);
    }

    /** @test */
    public function it_handles_numeric_values()
    {
        $tag = new Tag(2, 123);
        
        $this->assertEquals(2, $tag->getTag());
        $this->assertEquals('123', $tag->getValue());
        $this->assertEquals(3, $tag->getLength());
    }

    /** @test */
    public function it_handles_empty_values()
    {
        $tag = new Tag(3, '');
        
        $this->assertEquals(3, $tag->getTag());
        $this->assertEquals('', $tag->getValue());
        $this->assertEquals(0, $tag->getLength());
    }
}
