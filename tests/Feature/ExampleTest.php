<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response
            ->assertStatus(200)
            ->assertViewIs('welcome');
    }

    public function test_dashboard_remains_available_on_its_own_route(): void
    {
        $response = $this->get('/dashboard');

        $response
            ->assertStatus(200)
            ->assertViewIs('dashboard');
    }

    public function test_dashboard_is_available_through_the_public_uncached_route(): void
    {
        $response = $this->get('/tableau-de-bord');

        $response
            ->assertStatus(200)
            ->assertViewIs('dashboard');
    }
}
