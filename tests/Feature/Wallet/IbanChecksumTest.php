<?php

namespace Tests\Feature\Wallet;

use App\Models\Specialist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * رقم کنترلی شبا (ISO 13616، mod 97): شبای با اشتباه تایپی نباید ذخیره شود.
 */
class IbanChecksumTest extends TestCase
{
    use RefreshDatabase;

    private const VALID = '820540102680020817909002';

    private function actingSpecialist(): array
    {
        $user = User::factory()->create(['phone' => '09121234567']);
        $specialist = Specialist::factory()->create(['phone' => '09121234567', 'user_id' => $user->id]);

        return [$user, $specialist];
    }

    private function putIban(User $user, string $iban)
    {
        return $this->actingAs($user)->put(route('specialist.wallet.update-iban'), [
            'iban' => $iban,
            'account_holder_name' => 'علی رضایی',
            'bank_name' => 'بانک ملت',
        ]);
    }

    public static function typoIbans(): array
    {
        return [
            'two adjacent digits swapped' => ['820540102680020871909002'],
            'one digit mistyped' => ['820540102680020817909003'],
            'check digits swapped' => ['280540102680020817909002'],
            'all zeros' => ['000000000000000000000000'],
        ];
    }

    #[DataProvider('typoIbans')]
    public function test_an_iban_whose_check_digits_do_not_match_is_rejected(string $iban): void
    {
        [$user, $specialist] = $this->actingSpecialist();

        $response = $this->putIban($user, $iban);

        $response->assertSessionHasErrors('iban');
        $this->assertStringContainsString('رقم کنترلی', session('errors')->first('iban'));
        $this->assertNull($specialist->getOrCreateWallet()->fresh()->iban);
    }

    public function test_a_valid_iban_is_still_accepted_with_or_without_spaces(): void
    {
        [$user, $specialist] = $this->actingSpecialist();

        $this->putIban($user, '8205 4010 2680 0208 1790 9002')->assertSessionHasNoErrors();

        $this->assertSame('IR'.self::VALID, $specialist->getOrCreateWallet()->fresh()->iban);
    }

    public function test_the_length_message_is_kept_for_a_wrong_length(): void
    {
        [$user] = $this->actingSpecialist();

        $this->putIban($user, '12345')->assertSessionHasErrors('iban');

        $this->assertStringContainsString('۲۴ رقم', session('errors')->first('iban'));
    }

    public function test_a_non_string_iban_is_rejected_without_a_type_error(): void
    {
        [$user] = $this->actingSpecialist();

        $this->actingAs($user)->put(route('specialist.wallet.update-iban'), [
            'iban' => ['8205'],
            'account_holder_name' => 'علی رضایی',
            'bank_name' => 'بانک ملت',
        ])->assertSessionHasErrors('iban');
    }
}
