<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;

class ValidationResponseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // create an admin user model without persisting to avoid running migrations in tests
        $this->admin = new User([
            'email' => 'admin@example.com',
            'role' => 'admin',
            'password' => bcrypt('password')
        ]);
    }

    public function test_ajax_validation_returns_422_json()
    {
    $this->actingAs($this->admin);

        // Send JSON request with missing required fields
        $response = $this->postJson(route('admin.semester.store'), []);
        $response->assertStatus(422)
                 ->assertJsonStructure(['success', 'message', 'errors']);
    }

    public function test_web_validation_redirects_back_with_errors()
    {
    $this->actingAs($this->admin);

        $response = $this->post(route('admin.semester.store'), []);
        $response->assertStatus(302);
        $response->assertSessionHasErrors();
    }
}
