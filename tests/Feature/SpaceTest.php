<?php

namespace Tests\Feature;

use App\Models\Space;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_any_user_can_create_space(): void
    {
        $user = User::factory()->create();
        $user->assignRole('consumer');

        $response = $this->actingAs($user)
            ->postJson('/api/spaces', [
                'name' => 'Test Space',
                'description' => 'A test space',
                'address' => '123 Test St',
                'city' => 'Test City',
                'country' => 'Test Country',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'name' => 'Test Space',
                'owner_id' => $user->id,
            ]);

        $this->assertDatabaseHas('spaces', [
            'name' => 'Test Space',
            'owner_id' => $user->id,
        ]);
    }

    public function test_space_creation_requires_name(): void
    {
        $user = User::factory()->create();
        $user->assignRole('consumer');

        $response = $this->actingAs($user)
            ->postJson('/api/spaces', [
                'description' => 'A test space',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_unauthenticated_user_cannot_create_space(): void
    {
        $response = $this->postJson('/api/spaces', [
            'name' => 'Test Space',
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_view_all_active_spaces(): void
    {
        $user = User::factory()->create();
        $user->assignRole('consumer');

        $owner = User::factory()->create();
        $owner->assignRole('consumer');

        Space::factory()->count(3)->create([
            'owner_id' => $owner->id,
            'is_active' => true,
        ]);

        Space::factory()->create([
            'owner_id' => $owner->id,
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/spaces');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_spaces_endpoint_returns_paginated_results(): void
    {
        $user = User::factory()->create();
        $user->assignRole('consumer');

        $owner = User::factory()->create();
        $owner->assignRole('consumer');

        Space::factory()->count(20)->create([
            'owner_id' => $owner->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/spaces?per_page=10');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data',
                'current_page',
                'last_page',
                'per_page',
                'total',
            ]);
    }

    public function test_owner_can_view_their_managed_spaces(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('consumer');

        $otherOwner = User::factory()->create();
        $otherOwner->assignRole('consumer');

        Space::factory()->count(3)->create(['owner_id' => $owner->id]);

        Space::factory()->count(2)->create(['owner_id' => $otherOwner->id]);

        $response = $this->actingAs($owner)
            ->getJson('/api/spaces/managed');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_worker_can_view_assigned_spaces(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('consumer');

        $worker = User::factory()->create();
        $worker->assignRole('consumer');

        $space1 = Space::factory()->create(['owner_id' => $owner->id]);
        $space2 = Space::factory()->create(['owner_id' => $owner->id]);
        $space3 = Space::factory()->create(['owner_id' => $owner->id]);

        $space1->users()->attach($worker->id, ['role' => 'space_worker']);
        $space2->users()->attach($worker->id, ['role' => 'space_admin']);

        $response = $this->actingAs($worker)
            ->getJson('/api/spaces/managed');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_consumer_with_no_spaces_sees_empty_managed_list(): void
    {
        $consumer = User::factory()->create();
        $consumer->assignRole('consumer');

        $owner = User::factory()->create();
        $owner->assignRole('consumer');

        Space::factory()->count(3)->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($consumer)
            ->getJson('/api/spaces/managed');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_owner_can_view_specific_space(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('consumer');

        $space = Space::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner)
            ->getJson("/api/spaces/{$space->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $space->id,
                'name' => $space->name,
            ]);
    }

    public function test_anyone_can_view_active_space(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('consumer');

        $consumer = User::factory()->create();
        $consumer->assignRole('consumer');

        $space = Space::factory()->create([
            'owner_id' => $owner->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($consumer)
            ->getJson("/api/spaces/{$space->id}");

        $response->assertStatus(200);
    }

    public function test_only_managers_can_view_inactive_space(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('consumer');

        $consumer = User::factory()->create();
        $consumer->assignRole('consumer');

        $space = Space::factory()->create([
            'owner_id' => $owner->id,
            'is_active' => false,
        ]);

        $response = $this->actingAs($consumer)
            ->getJson("/api/spaces/{$space->id}");

        $response->assertStatus(403);

        $response = $this->actingAs($owner)
            ->getJson("/api/spaces/{$space->id}");

        $response->assertStatus(200);
    }

    public function test_owner_can_update_their_space(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('consumer');

        $space = Space::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner)
            ->putJson("/api/spaces/{$space->id}", [
                'name' => 'Updated Space Name',
                'description' => 'Updated description',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'name' => 'Updated Space Name',
                'description' => 'Updated description',
            ]);

        $this->assertDatabaseHas('spaces', [
            'id' => $space->id,
            'name' => 'Updated Space Name',
        ]);
    }

    public function test_space_admin_can_update_space(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('consumer');

        $admin = User::factory()->create();
        $admin->assignRole('consumer');

        $space = Space::factory()->create(['owner_id' => $owner->id]);
        $space->users()->attach($admin->id, ['role' => 'space_admin']);

        $response = $this->actingAs($admin)
            ->putJson("/api/spaces/{$space->id}", [
                'name' => 'Admin Updated Name',
            ]);

        $response->assertStatus(200);
    }

    public function test_space_worker_cannot_update_space(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('consumer');

        $worker = User::factory()->create();
        $worker->assignRole('consumer');

        $space = Space::factory()->create(['owner_id' => $owner->id]);
        $space->users()->attach($worker->id, ['role' => 'space_worker']);

        $response = $this->actingAs($worker)
            ->putJson("/api/spaces/{$space->id}", [
                'name' => 'Worker Tried to Update',
            ]);

        $response->assertStatus(403);
    }

    public function test_owner_can_delete_their_space(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('consumer');

        $space = Space::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner)
            ->deleteJson("/api/spaces/{$space->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('spaces', ['id' => $space->id]);
    }

    public function test_space_admin_cannot_delete_space(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('consumer');

        $admin = User::factory()->create();
        $admin->assignRole('consumer');

        $space = Space::factory()->create(['owner_id' => $owner->id]);
        $space->users()->attach($admin->id, ['role' => 'space_admin']);

        $response = $this->actingAs($admin)
            ->deleteJson("/api/spaces/{$space->id}");

        $response->assertStatus(403);
    }

    public function test_owner_can_assign_worker_to_space(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('consumer');

        $worker = User::factory()->create();
        $worker->assignRole('consumer');

        $space = Space::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner)
            ->postJson("/api/spaces/{$space->id}/assign-user", [
                'user_id' => $worker->id,
                'role' => 'space_worker',
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'User assigned successfully']);

        $this->assertDatabaseHas('space_user', [
            'space_id' => $space->id,
            'user_id' => $worker->id,
            'role' => 'space_worker',
        ]);
    }

    public function test_owner_can_assign_admin_to_space(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('consumer');

        $admin = User::factory()->create();
        $admin->assignRole('consumer');

        $space = Space::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner)
            ->postJson("/api/spaces/{$space->id}/assign-user", [
                'user_id' => $admin->id,
                'role' => 'space_admin',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('space_user', [
            'space_id' => $space->id,
            'user_id' => $admin->id,
            'role' => 'space_admin',
        ]);
    }

    public function test_reassigning_user_updates_their_role(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('consumer');

        $user = User::factory()->create();
        $user->assignRole('consumer');

        $space = Space::factory()->create(['owner_id' => $owner->id]);
        $space->users()->attach($user->id, ['role' => 'space_worker']);

        $response = $this->actingAs($owner)
            ->postJson("/api/spaces/{$space->id}/assign-user", [
                'user_id' => $user->id,
                'role' => 'space_admin',
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'User role updated successfully']);

        $this->assertDatabaseHas('space_user', [
            'space_id' => $space->id,
            'user_id' => $user->id,
            'role' => 'space_admin',
        ]);
    }

    public function test_worker_cannot_assign_users(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('consumer');

        $worker = User::factory()->create();
        $worker->assignRole('consumer');

        $newUser = User::factory()->create();
        $newUser->assignRole('consumer');

        $space = Space::factory()->create(['owner_id' => $owner->id]);
        $space->users()->attach($worker->id, ['role' => 'space_worker']);

        $response = $this->actingAs($worker)
            ->postJson("/api/spaces/{$space->id}/assign-user", [
                'user_id' => $newUser->id,
                'role' => 'space_worker',
            ]);

        $response->assertStatus(403);
    }

    public function test_owner_can_remove_user_from_space(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('consumer');

        $worker = User::factory()->create();
        $worker->assignRole('consumer');

        $space = Space::factory()->create(['owner_id' => $owner->id]);
        $space->users()->attach($worker->id, ['role' => 'space_worker']);

        $response = $this->actingAs($owner)
            ->deleteJson("/api/spaces/{$space->id}/users/{$worker->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'User removed successfully']);

        $this->assertDatabaseMissing('space_user', [
            'space_id' => $space->id,
            'user_id' => $worker->id,
        ]);
    }

    public function test_can_view_all_users_of_a_space(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('consumer');

        $admin = User::factory()->create(['name' => 'Admin User']);
        $admin->assignRole('consumer');

        $worker = User::factory()->create(['name' => 'Worker User']);
        $worker->assignRole('consumer');

        $space = Space::factory()->create(['owner_id' => $owner->id]);
        $space->users()->attach($admin->id, ['role' => 'space_admin']);
        $space->users()->attach($worker->id, ['role' => 'space_worker']);

        $response = $this->actingAs($owner)
            ->getJson("/api/spaces/{$space->id}/users");

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonFragment(['name' => 'Admin User', 'role' => 'space_admin'])
            ->assertJsonFragment(['name' => 'Worker User', 'role' => 'space_worker']);
    }
}