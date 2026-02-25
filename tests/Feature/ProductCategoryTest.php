<?php

namespace Tests\Feature;

use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProductCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadminUser;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadminUser = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@buy-local.com',
            'city' => 'Satu Mare',
            'country' => 'Romania'
        ]);
        //TODO: implement roles for future testing
        // $this->superadminUser->assignRole('superadmin');

        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'city' => 'Cluj-Napoca',
            'country' => 'Romania',
        ]);
    }

    /** @test */
    public function guests_can_view_products_list()
    {
        // Arrange
        ProductCategory::factory()->count(3)->create();

        // Act
        $response = $this->getJson('/api/categories');

        // Assert
        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                ]
            ]
        ]);

        $response->assertJsonCount(3, 'data');
    }
}
