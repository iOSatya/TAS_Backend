<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test non-admin cannot access admin endpoints.
     */
    public function test_non_admin_cannot_access_admin_endpoints(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/admin/users');
        $response->assertStatus(403);
    }

    /**
     * Test admin can list users with pagination.
     */
    public function test_admin_can_list_users(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->count(5)->create();
        Sanctum::actingAs($admin, ['*']);

        $response = $this->getJson('/api/admin/users');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'links',
            ])
            ->assertJsonCount(6, 'data'); // admin + 5 users
    }

    /**
     * Test admin can search users.
     */
    public function test_admin_can_search_users(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);
        Sanctum::actingAs($admin, ['*']);

        $response = $this->getJson('/api/admin/users?search=john');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'John Doe');
    }

    /**
     * Test admin can filter by admin status.
     */
    public function test_admin_can_filter_by_admin_status(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->count(3)->create(['is_admin' => false]);
        Sanctum::actingAs($admin, ['*']);

        $response = $this->getJson('/api/admin/users?is_admin=true');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data'); // only admin
    }

    /**
     * Test admin can view a single user.
     */
    public function test_admin_can_view_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        Sanctum::actingAs($admin, ['*']);

        $response = $this->getJson('/api/admin/users/' . $user->id);
        $response->assertStatus(200)
            ->assertJsonStructure(['user' => ['id', 'name', 'email', 'is_admin', 'created_at', 'updated_at']])
            ->assertJsonPath('user.id', $user->id);
    }

    /**
     * Test admin cannot view non-existent user.
     */
    public function test_admin_cannot_view_non_existent_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Sanctum::actingAs($admin, ['*']);

        $response = $this->getJson('/api/admin/users/999');
        $response->assertStatus(404);
    }

    /**
     * Test admin can create a new user.
     */
    public function test_admin_can_create_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Sanctum::actingAs($admin, ['*']);

        $response = $this->postJson('/api/admin/users', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password123',
            'is_admin' => false,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email', 'is_admin'],
            ])
            ->assertJsonPath('user.name', 'New User')
            ->assertJsonPath('user.is_admin', false);

        $this->assertDatabaseHas('users', [
            'email' => 'new@example.com',
            'is_admin' => false,
        ]);
    }

    /**
     * Test admin can create admin user.
     */
    public function test_admin_can_create_admin_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Sanctum::actingAs($admin, ['*']);

        $response = $this->postJson('/api/admin/users', [
            'name' => 'Another Admin',
            'email' => 'admin2@example.com',
            'password' => 'password123',
            'is_admin' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('user.is_admin', true);

        $this->assertDatabaseHas('users', [
            'email' => 'admin2@example.com',
            'is_admin' => true,
        ]);
    }

    /**
     * Test user creation validation errors.
     */
    public function test_user_creation_validation_errors(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Sanctum::actingAs($admin, ['*']);

        $response = $this->postJson('/api/admin/users', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    /**
     * Test admin can update user details.
     */
    public function test_admin_can_update_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_admin' => false]);
        Sanctum::actingAs($admin, ['*']);

        $response = $this->putJson('/api/admin/users/' . $user->id, [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'is_admin' => true,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.name', 'Updated Name')
            ->assertJsonPath('user.email', 'updated@example.com')
            ->assertJsonPath('user.is_admin', true);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'is_admin' => true,
        ]);
    }

    /**
     * Test admin cannot remove their own admin status.
     */
    public function test_admin_cannot_remove_own_admin_status(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Sanctum::actingAs($admin, ['*']);

        $response = $this->putJson('/api/admin/users/' . $admin->id, [
            'is_admin' => false,
        ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'You cannot remove your own admin status']);
    }

    /**
     * Test admin can delete a user.
     */
    public function test_admin_can_delete_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        Sanctum::actingAs($admin, ['*']);

        $response = $this->deleteJson('/api/admin/users/' . $user->id);
        $response->assertStatus(200)
            ->assertJson(['message' => 'User deleted successfully']);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    /**
     * Test admin cannot delete themselves.
     */
    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Sanctum::actingAs($admin, ['*']);

        $response = $this->deleteJson('/api/admin/users/' . $admin->id);
        $response->assertStatus(422)
            ->assertJson(['message' => 'You cannot delete your own account']);
    }

    /**
     * Test admin can reset user password.
     */
    public function test_admin_can_reset_user_password(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        Sanctum::actingAs($admin, ['*']);

        $response = $this->postJson('/api/admin/users/' . $user->id . '/reset-password');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'new_password',
                'user' => ['id', 'email'],
            ]);

        // Verify password was changed (old password no longer works)
        $this->assertFalse(Hash::check('password', $user->fresh()->password));
    }

    /**
     * Test admin can toggle admin status for another user.
     */
    public function test_admin_can_toggle_admin_status(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_admin' => false]);
        Sanctum::actingAs($admin, ['*']);

        // Grant admin status
        $response = $this->putJson('/api/admin/users/' . $user->id . '/admin-status');
        $response->assertStatus(200)
            ->assertJsonPath('user.is_admin', true);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_admin' => true]);

        // Revoke admin status
        $response = $this->putJson('/api/admin/users/' . $user->id . '/admin-status');
        $response->assertStatus(200)
            ->assertJsonPath('user.is_admin', false);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_admin' => false]);
    }

    /**
     * Test admin cannot toggle their own admin status.
     */
    public function test_admin_cannot_toggle_own_admin_status(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Sanctum::actingAs($admin, ['*']);

        $response = $this->putJson('/api/admin/users/' . $admin->id . '/admin-status');
        $response->assertStatus(422)
            ->assertJson(['message' => 'You cannot remove your own admin status']);
    }
}