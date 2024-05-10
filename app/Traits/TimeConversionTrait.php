<?php

namespace App\Traits;

trait TimeConversionTrait
{
    /*
     *  This trait converts decimal time values to strings and vice-versa.
     *  Example: 8.5 -> 8:30
    */
    private function convertDecimalToTime(float $hoursDecimal): string
    {
        $sign = $hoursDecimal < 0 ? '-' : '';
        $hoursDecimal = abs($hoursDecimal);

        $hours = intval($hoursDecimal);
        $decimalHours = $hoursDecimal - $hours;
        $minutes = round($decimalHours * 60);

        if ($minutes == 60) {
            $hours += 1;
            $minutes = 0;
        }

        return sprintf("%s%d:%02d", $sign, $hours, $minutes);
    }

    private function convertTimeToDecimal(string $time): float
    {
        [$hours, $minutes] = explode(':', $time);
        return $hours + ($minutes / 60);
    }
}
