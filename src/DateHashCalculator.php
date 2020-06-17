<?php

namespace CsvAggregator;

class DateHashCalculator implements HashCalculatorInterface
{
    public function calculate(string $source): string {
        return str_replace('-', '', $source);
    }

    public function hashToReadableName(string $hash): string
    {
        return preg_replace("/([\d]{4})([\d]{2})([\d]{2})/", "$1-$2-$3", $hash);
    }
}
