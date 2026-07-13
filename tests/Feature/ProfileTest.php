<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_role_can_open_their_own_profile(): void
    {
        foreach (UserRole::cases() as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user)->get('/profile')->assertOk()->assertSee('My Profile');
        }
    }

    public function test_user_can_update_their_personal_information(): void
    {
        $user = User::factory()->create(['role' => UserRole::Viewer]);

        $this->actingAs($user)
            ->put('/profile', [
                'first_name' => 'Nadia',
                'last_name' => 'Berrada',
                'email' => 'nadia.berrada@smartsecurity.test',
                'phone' => '+212 600 000 000',
            ])
            ->assertRedirect('/profile')
            ->assertSessionHas('status');

        $user->refresh();
        $this->assertSame('Nadia', $user->first_name);
        $this->assertSame('nadia.berrada@smartsecurity.test', $user->email);
        $this->assertSame('+212 600 000 000', $user->phone);
    }

    public function test_email_cannot_be_taken_from_another_account(): void
    {
        $other = User::factory()->create(['email' => 'taken@smartsecurity.test']);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/profile')
            ->put('/profile', [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => 'taken@smartsecurity.test',
            ])
            ->assertRedirect('/profile')
            ->assertSessionHasErrors('email');

        $this->assertNotSame($other->email, $user->refresh()->email);
    }

    public function test_avatar_upload_is_stored_and_linked(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->put('/profile', [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'avatar' => UploadedFile::fake()->image('me.jpg', 300, 300),
        ])->assertRedirect('/profile');

        $user->refresh();
        $this->assertNotNull($user->avatar);
        Storage::disk('public')->assertExists($user->avatar);
    }

    public function test_password_change_requires_the_current_password(): void
    {
        $user = User::factory()->create(['password' => 'old-Password1']);

        $this->actingAs($user)
            ->from('/profile')
            ->put('/profile/password', [
                'current_password' => 'wrong-guess',
                'password' => 'new-Password1',
                'password_confirmation' => 'new-Password1',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('old-Password1', $user->refresh()->password));
    }

    public function test_password_is_changed_with_valid_current_password(): void
    {
        $user = User::factory()->create(['password' => 'old-Password1']);

        $this->actingAs($user)
            ->put('/profile/password', [
                'current_password' => 'old-Password1',
                'password' => 'new-Password1',
                'password_confirmation' => 'new-Password1',
            ])
            ->assertRedirect('/profile')
            ->assertSessionHas('status');

        $this->assertTrue(Hash::check('new-Password1', $user->refresh()->password));
    }
}
