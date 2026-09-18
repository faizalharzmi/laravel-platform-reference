<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_create_a_project_for_their_workspace(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::create(['name' => 'Acme', 'slug' => 'acme']);
        $workspace->users()->attach($user, ['role' => 'admin']);
        Sanctum::actingAs($user, ['projects:create']);

        $response = $this->postJson("/api/v1/workspaces/{$workspace->id}/projects", [
            'name' => 'Billing API',
            'key' => 'bill',
        ]);

        $response->assertCreated()->assertJsonPath('data.key', 'BILL');
        $this->assertDatabaseHas('projects', ['workspace_id' => $workspace->id, 'key' => 'BILL']);
    }

    public function test_a_regular_member_cannot_create_a_project(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::create(['name' => 'Acme', 'slug' => 'acme']);
        $workspace->users()->attach($user, ['role' => 'member']);
        Sanctum::actingAs($user, ['projects:create']);

        $this->postJson("/api/v1/workspaces/{$workspace->id}/projects", [
            'name' => 'Billing API',
            'key' => 'bill',
        ])->assertForbidden();
    }

    public function test_a_user_cannot_create_a_project_in_another_workspace(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::create(['name' => 'Other', 'slug' => 'other']);
        Sanctum::actingAs($user, ['projects:create']);

        $this->postJson("/api/v1/workspaces/{$workspace->id}/projects", [
            'name' => 'Billing API',
            'key' => 'bill',
        ])->assertForbidden();
    }

    public function test_project_key_is_required_and_bounded(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::create(['name' => 'Acme', 'slug' => 'acme']);
        $workspace->users()->attach($user, ['role' => 'owner']);
        Sanctum::actingAs($user, ['projects:create']);

        $this->postJson("/api/v1/workspaces/{$workspace->id}/projects", [
            'name' => 'A',
            'key' => 'not valid',
        ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'key']);
    }
}
