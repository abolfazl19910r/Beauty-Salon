<?php

namespace Tests\Feature\Specialist;

use App\Models\Specialist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dedicated coverage for the specialist IBAN edit/update flow, beyond the single
 * happy-path case already covered in SpecialistSelfServiceAuthorizationTest.
 *
 * Also documents (via test_dead_nested_form_request_is_never_wired_to_the_controller)
 * that App\Http\Requests\Specialist\Wallet\Iban\UpdateIbanRequest is completely dead
 * code — the controller uses the root-level App\Http\Requests\Specialist\UpdateIbanRequest
 * instead (already fixed in session 6 to not gate on the nonexistent 'specialist' role).
 */
class SpecialistIbanControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actingSpecialist(): array
    {
        $user = User::factory()->create(['phone' => '09121234567']);
        $specialist = Specialist::factory()->create([
            'phone' => '09121234567',
            'user_id' => $user->id,
        ]);

        return [$user, $specialist];
    }

    public function test_edit_page_renders_with_the_specialists_wallet(): void
    {
        [$user, $specialist] = $this->actingSpecialist();

        $response = $this->actingAs($user)->get(route('specialist.wallet.edit-iban'));

        $response->assertOk();
        $response->assertViewIs('specialist.wallet.edit-iban');
    }

    public function test_edit_page_shows_profile_not_found_when_no_specialist_matches(): void
    {
        $user = User::factory()->create(['phone' => '09120000000']);

        $response = $this->actingAs($user)->get(route('specialist.wallet.edit-iban'));

        $response->assertOk();
        $response->assertViewIs('specialist.profile-not-found');
    }

    public function test_update_prefixes_the_stored_iban_with_ir(): void
    {
        [$user, $specialist] = $this->actingSpecialist();

        $this->actingAs($user)->put(route('specialist.wallet.update-iban'), [
            'iban' => '820540102680020817909002',
            'account_holder_name' => 'علی رضایی',
            'bank_name' => 'بانک ملت',
        ]);

        $this->assertSame(
            'IR820540102680020817909002',
            $specialist->getOrCreateWallet()->fresh()->iban
        );
    }

    public function test_update_strips_spaces_from_the_iban_before_storing(): void
    {
        [$user, $specialist] = $this->actingSpecialist();

        $this->actingAs($user)->put(route('specialist.wallet.update-iban'), [
            'iban' => '8205 4010 2680 0208 1790 9002',
            'account_holder_name' => 'علی رضایی',
            'bank_name' => 'بانک ملت',
        ]);

        $this->assertSame(
            'IR820540102680020817909002',
            $specialist->getOrCreateWallet()->fresh()->iban
        );
    }

    public function test_update_resets_iban_verified_flag_to_false(): void
    {
        [$user, $specialist] = $this->actingSpecialist();
        $wallet = $specialist->getOrCreateWallet();
        $wallet->update(['iban_verified' => true]);

        $this->actingAs($user)->put(route('specialist.wallet.update-iban'), [
            'iban' => '820540102680020817909002',
            'account_holder_name' => 'علی رضایی',
            'bank_name' => 'بانک ملت',
        ]);

        $this->assertFalse((bool) $wallet->fresh()->iban_verified);
    }

    public function test_update_rejects_an_iban_with_fewer_than_24_digits(): void
    {
        [$user] = $this->actingSpecialist();

        $response = $this->actingAs($user)->put(route('specialist.wallet.update-iban'), [
            'iban' => '12345',
            'account_holder_name' => 'علی رضایی',
            'bank_name' => 'بانک ملت',
        ]);

        $response->assertSessionHasErrors('iban');
    }

    public function test_update_rejects_an_iban_containing_non_digit_characters(): void
    {
        [$user] = $this->actingSpecialist();

        $response = $this->actingAs($user)->put(route('specialist.wallet.update-iban'), [
            'iban' => 'IR820540102680020817909002',
            'account_holder_name' => 'علی رضایی',
            'bank_name' => 'بانک ملت',
        ]);

        // The rule only strips spaces, not the "IR" prefix — a value that
        // already includes it should be rejected by the 24-digit pattern.
        $response->assertSessionHasErrors('iban');
    }

    public function test_update_requires_account_holder_name(): void
    {
        [$user] = $this->actingSpecialist();

        $response = $this->actingAs($user)->put(route('specialist.wallet.update-iban'), [
            'iban' => '820540102680020817909002',
            'bank_name' => 'بانک ملت',
        ]);

        $response->assertSessionHasErrors('account_holder_name');
    }

    public function test_update_rejects_an_account_holder_name_shorter_than_three_characters(): void
    {
        [$user] = $this->actingSpecialist();

        $response = $this->actingAs($user)->put(route('specialist.wallet.update-iban'), [
            'iban' => '820540102680020817909002',
            'account_holder_name' => 'ع',
            'bank_name' => 'بانک ملت',
        ]);

        $response->assertSessionHasErrors('account_holder_name');
    }

    public function test_update_requires_bank_name(): void
    {
        [$user] = $this->actingSpecialist();

        $response = $this->actingAs($user)->put(route('specialist.wallet.update-iban'), [
            'iban' => '820540102680020817909002',
            'account_holder_name' => 'علی رضایی',
        ]);

        $response->assertSessionHasErrors('bank_name');
    }

    public function test_update_returns_profile_not_found_when_no_specialist_matches(): void
    {
        $user = User::factory()->create(['phone' => '09120000000']);

        $response = $this->actingAs($user)->put(route('specialist.wallet.update-iban'), [
            'iban' => '820540102680020817909002',
            'account_holder_name' => 'علی رضایی',
            'bank_name' => 'بانک ملت',
        ]);

        $response->assertOk();
        $response->assertViewIs('specialist.profile-not-found');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('specialist.wallet.edit-iban'))->assertRedirect(route('login'));
        $this->put(route('specialist.wallet.update-iban'), [])->assertRedirect(route('login'));
    }

    public function test_the_controller_uses_the_real_root_level_form_request(): void
    {
        // ⭐ Removed in test-writing session 7 (2026-08-18): a duplicate, completely
        // dead App\Http\Requests\Specialist\Wallet\Iban\UpdateIbanRequest used to exist
        // on disk alongside this one, never wired to any controller. It was deleted as
        // part of R-Cleanup-DeadCode housekeeping. This just pins that the controller
        // still uses the real, root-level UpdateIbanRequest (with its correct
        // auth()->check() fix from session 6).
        $reflection = new \ReflectionClass(\App\Http\Controllers\Specialist\Wallet\Iban\SpecialistIbanController::class);
        $updateMethod = $reflection->getMethod('update');
        $params = $updateMethod->getParameters();

        $this->assertSame(
            \App\Http\Requests\Specialist\UpdateIbanRequest::class,
            $params[0]->getType()->getName()
        );
    }
}
