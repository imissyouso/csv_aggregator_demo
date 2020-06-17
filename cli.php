<?php
require_once 'vendor/autoload.php';

use CsvAggregator\CsvMetricsReader;
use CSVAggregator\CsvMetricsReaderException;
use CsvAggregator\DateHashCalculator;
use CsvAggregator\HashMapRowAggregator;

$path = $argv[1];
if(empty($path)){
    echo "You must provide the directory path to scan!\n";
    exit(0);
}

$rowHandler = new HashMapRowAggregator(new DateHashCalculator(), 1);
$aggregator = new CsvMetricsReader($path, $rowHandler);

try {
    $aggregator->run();
    echo "Done! Check the result.csv\n";
} catch (CsvMetricsReaderException $e) {
    var_dump($e);
}
