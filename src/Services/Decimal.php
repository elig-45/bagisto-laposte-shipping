<?php

declare(strict_types=1);

namespace MEJF\LaPosteShipping\Services;

use InvalidArgumentException;

/** Decimal math uses integers only; at most six fractional places. */
final class Decimal
{
    public static function scaled(string|int|float $value, int $digits = 6): int
    {
        if ($digits < 0 || $digits > 6) {
            throw new InvalidArgumentException('Unsupported decimal precision.');
        }
        $text = is_float($value) ? sprintf('%.6F', $value) : trim((string) $value);
        if (! preg_match('/^\d{1,8}(?:\.\d{1,6})?$/D', $text)) {
            throw new InvalidArgumentException('Invalid non-negative decimal.');
        }
        [$whole, $fraction] = array_pad(explode('.', $text, 2), 2, '');
        if (strlen($fraction) > $digits) {
            if (trim(substr($fraction, $digits), '0') !== '') {
                throw new InvalidArgumentException('Too many decimal places.');
            }
            $fraction = substr($fraction, 0, $digits);
        }
        return (int) $whole * 10 ** $digits + (int) str_pad($fraction, $digits, '0');
    }

    public static function weightMg(string|int|float $value, string $unit): int
    {
        $scaled = self::scaled($value); // parts per million of a weight unit
        $mgPerUnit = match ($unit) {
            'kgs' => 1_000_000,
            'lbs' => 453_592_370,
            default => throw new InvalidArgumentException('Unsupported Bagisto weight unit.'),
        };
        // Round UP to the next milligram. Never underbill due to conversion.
        return intdiv($scaled * $mgPerUnit + 999_999, 1_000_000);
    }

    public static function gramsMg(string|int|float $grams): int
    {
        return intdiv(self::scaled($grams) + 999, 1000);
    }

    public static function euroCents(string|int|float $euros): int
    {
        return self::scaled($euros, 2);
    }

    public static function percentBasisPoints(string|int|float $percent): int
    {
        $bps = self::scaled($percent, 2);
        if ($bps > 10_000) {
            throw new InvalidArgumentException('Percentage must not exceed 100%.');
        }
        return $bps;
    }
}
