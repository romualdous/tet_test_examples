<?php

namespace Tests\Unit;

use App\Utils\Code;
use Mockery;
use PHPUnit\Framework\TestCase;

class CodeServiceTest extends TestCase
{
    /** @test */
    public function it_generates_a_code()
    {
        $this->assertIsInt((new Code())->generate());
    }

    public function it_can_be_mocked()
    {
        $expectedCode = 123456;

        $mock = Mockery::mock(Code::class);
        $mock->shouldReceive('generate')
            ->times(1)
            ->withNoArgs()
            ->andReturn($expectedCode);
        app()->instance(Code::class, $mock);

        $this->assertEquals($expectedCode, (new Code)->generate());
    }
}
