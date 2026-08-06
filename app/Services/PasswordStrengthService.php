<?php

namespace App\Services;

/**
 * Previously, this calculation was inside the SecurityController and each time the security score was calculated,
 * it was rerun on the stored hash of the password itself (not the raw password) — since the hash is always a string
 * of fixed length and full of character variations, this always gave a high score, regardless of the actual strength of the password.
 *
 * This service should only be called at the moment of registration/change of password, on the raw password itself (before Hash::make); the result is stored in the column users.password_strength_score and from then on
 * it is always read from that column, not reconstructed from the hash.
 */
class PasswordStrengthService
{
    /**
     * @return int score 0 to 10
     */
    public function score(string $password): int
    {
        $score = 0;

        $length = strlen($password);
        if ($length >= 12) {
            $score += 3;
        } elseif ($length >= 10) {
            $score += 2;
        } elseif ($length >= 8) {
            $score += 1;
        }

        if (preg_match('/[A-Z]/', $password)) {
            $score += 2;
        }
        if (preg_match('/[a-z]/', $password)) {
            $score += 2;
        }
        if (preg_match('/[0-9]/', $password)) {
            $score += 2;
        }
        if (preg_match('/[^A-Za-z0-9]/', $password)) {
            $score += 3;
        }

        $uniqueChars = count(array_unique(str_split($password)));
        if ($uniqueChars >= 8) {
            $score += 2;
        }

        if (preg_match('/(.)\1{2,}/', $password)) {
            $score -= 2;
        }
        if (preg_match('/^(?=.*[0-9])(?=.*[a-zA-Z])(?=.*[^A-Za-z0-9]).{8,}$/', $password)) {
            $score += 2;
        }

        $commonPasswords = ['password', '123456', 'qwerty', 'admin', '123456789', '12345'];
        if (in_array(strtolower($password), $commonPasswords, true)) {
            $score = 0;
        }

        return max(0, min(10, $score));
    }
}
