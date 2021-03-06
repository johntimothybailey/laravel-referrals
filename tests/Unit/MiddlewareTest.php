<?php

namespace Pdazcom\Referrals\Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Orchestra\Testbench\Traits\WithLoadMigrationsFrom;
use Pdazcom\Referrals\Http\Middleware\StoreReferralCode;
use Pdazcom\Referrals\Models\ReferralProgram;
use Pdazcom\Referrals\Tests\TestCase;
use Mockery as m;

class MiddlewareTest extends TestCase
{
    use WithLoadMigrationsFrom;
    use DatabaseTransactions;
    use DatabaseMigrations;

    public function setUp()
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    public function testSetCookie()
    {
        $program = ReferralProgram::create([
            'name' => 'test',
            'title' => 'Test',
            'description' => 'Test description',
            'uri' => 'test',
        ]);

        $refLink = $program->links()->create([
            'user_id' => 1,
        ]);

        $request = m::mock(Request::class)->makePartial();

        $request->shouldReceive('has')->once()->with('ref')->andReturn(true);
        $request->shouldReceive('get')->once()->with('ref')->andReturn($refLink->code);
        $request->shouldReceive('url')->once()->andReturn('/');

        $middleware = new StoreReferralCode();
        $response = $middleware->handle($request, function ($request) {
            return response('');
        });

        // is this redirect?
        $this->assertEquals(302, $response->getStatusCode());

        // is cookie was set
        $this->assertCount(1, $response->headers->getCookies());
        $cookie = $response->headers->getCookies()[0];

        // checking name and value of cookie
        $this->assertEquals('ref', $cookie->getName());
        $this->assertEquals($refLink->id, $cookie->getValue());
    }
}
