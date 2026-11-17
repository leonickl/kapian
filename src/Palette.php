<?php

namespace App;

class Palette
{
    /**
     * Generate $n approximately equispaced
     * colour shades from dark to light.
     */
    public static function shades(
        int $n,
        float $hue,
        float $saturation = 1.0,
        float $lMin = 0.25,
        float $lMax = 0.85,
    ): array {
        if ($n < 1) {
            return [];
        }

        $shades = [];

        for ($i = 0; $i < $n; $i++) {
            $l = ($n === 1)
                ? ($lMin + $lMax) / 2
                : $lMin + ($lMax - $lMin) * $i / ($n - 1);

            $shades[] = self::hslToHex($hue, $saturation, $l);
        }

        return $shades;
    }

    private static function hslToHex(float $h, float $s, float $l): string
    {
        $a = $s * min($l, 1 - $l);

        $f = function (int $nOff) use ($h, $l, $a): int {
            $k = fmod($nOff + $h / 30, 12);
            $c = $l - $a * max(-1, min($k - 3, 9 - $k, 1));

            return (int) round($c * 255);
        };

        return sprintf('#%02x%02x%02x', $f(0), $f(8), $f(4));
    }
}
