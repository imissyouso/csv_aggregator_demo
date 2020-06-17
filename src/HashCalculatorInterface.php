<?php
namespace CsvAggregator;

interface HashCalculatorInterface
{
    public function calculate(string $source): string;
    public function hashToReadableName(string $source): string;
}
