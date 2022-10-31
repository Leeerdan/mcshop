<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use DatabaseTransactions;
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_the_application_returns_a_successful_response()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function testRegister()
    {
        $response = $this->post('wx/auth/register', [
            'username' => 'erdan4',
            'password' => '123456',
            'mobile'   => '15000000004',
            'code'     => '1234'
        ]);
        $response->assertStatus(200);
        $res = $response->getOriginalContent();
        $this->assertEquals(0, $res['errno']);
        $this->assertNotEmpty($res['data']);
    }
}
