<?php

namespace Tests\Unit\Services;

use App\Exceptions\Services\Text2ReachServiceException;
use App\Services\Text2ReachService;
use Mockery;
use PHPUnit\Framework\TestCase;

class Text2ReachServiceTest extends TestCase
{
    /**
     * @var Text2ReachService
     */
    private Text2ReachService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = Mockery::mock(Text2ReachService::class);
        $this->setUpExpectations();
    }

    /** @test */
    public function failed_response_gets_registered_as_failed()
    {
        // Normal response (message ID is being sent back)
        $this->assertFalse($this->service->isErrorResponse(124534));

        // Error codes
        $this->assertTrue($this->service->isErrorResponse(-10));
        $this->assertTrue($this->service->isErrorResponse('-10'));

        $this->expectException(Text2ReachServiceException::class);

        $this->service->send('+3712917980', 'test');
    }

    /**
     * Mock the requests and responses for Text2ReachService.
     */
    private function setUpExpectations(): void
    {
        $this->service->shouldReceive('send')
            ->withArgs(['+37129179850', 124534])
            ->andReturn(true);

        $this->service->shouldReceive('send')
            ->withArgs(['+3712917980', 'test'])
            ->andThrow(new Text2ReachServiceException('Wrong destination status'));

        $this->service->shouldReceive('isErrorResponse')
            ->withArgs([-10])
            ->andReturn(true);

        $this->service->shouldReceive('isErrorResponse')
            ->withArgs([124534])
            ->andReturn(false);
    }
}
