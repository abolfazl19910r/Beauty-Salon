<?php

namespace Tests\Feature\User;

use App\Models\BeautyService;
use App\Models\Specialist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_shows_the_latest_6_services_and_4_specialists(): void
    {
        BeautyService::factory()->count(8)->create();
        Specialist::factory()->count(6)->create();

        $response = $this->get('/');

        $response->assertOk();
        $this->assertCount(6, $response->viewData('services'));
        $this->assertCount(4, $response->viewData('specialists'));
    }

    public function test_index_caches_the_service_and_specialist_lists(): void
    {
        Cache::flush();
        BeautyService::factory()->count(2)->create();

        $this->get('/');

        $this->assertTrue(Cache::has('home_services'));
        $this->assertTrue(Cache::has('home_specialists'));
    }

    public function test_index_is_publicly_accessible_without_authentication(): void
    {
        $this->get('/')->assertOk();
    }
}
