<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationJoinRequest;
use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrganizationMembershipFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_invite_user_and_user_can_accept_and_member_can_request_join(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::create([
            'name' => 'Test Org',
            'description' => 'desc',
            'type' => 'charity',
            'status' => 'approved',
            'document_path' => 'docs/test.pdf',
            'owner_id' => $owner->id,
        ]);

        $invitedUser = User::factory()->create();

        Sanctum::actingAs($owner, ['*']);
        $inviteResponse = $this->postJson('/api/member/add', [
            'user_id' => $invitedUser->id,
            'role' => 'member',
        ]);

        $inviteResponse->assertOk();
        $this->assertDatabaseHas('organization_invitations', [
            'organization_id' => $organization->id,
            'user_id' => $invitedUser->id,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($invitedUser, ['*']);
        $acceptResponse = $this->postJson('/api/invitations/' . OrganizationInvitation::latest()->first()->id . '/respond', [
            'action' => 'accept',
        ]);

        $acceptResponse->assertOk();
        $this->assertDatabaseHas('organization_invitations', [
            'id' => OrganizationInvitation::latest()->first()->id,
            'status' => 'accepted',
        ]);
        $this->assertDatabaseHas('organization_members', [
            'organization_id' => $organization->id,
            'user_id' => $invitedUser->id,
            'status' => 'approved',
        ]);

        $joinRequester = User::factory()->create();
        Sanctum::actingAs($joinRequester, ['*']);
        $requestResponse = $this->postJson('/api/organizations/' . $organization->id . '/join');

        $requestResponse->assertOk();
        $this->assertDatabaseHas('organization_join_requests', [
            'organization_id' => $organization->id,
            'user_id' => $joinRequester->id,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($owner, ['*']);
        $respondJoinResponse = $this->postJson('/api/join-requests/' . OrganizationJoinRequest::latest()->first()->id . '/respond', [
            'action' => 'accept',
        ]);

        $respondJoinResponse->assertOk();
        $this->assertDatabaseHas('organization_join_requests', [
            'id' => OrganizationJoinRequest::latest()->first()->id,
            'status' => 'accepted',
        ]);
        $this->assertDatabaseHas('organization_members', [
            'organization_id' => $organization->id,
            'user_id' => $joinRequester->id,
            'status' => 'approved',
        ]);
    }
}
