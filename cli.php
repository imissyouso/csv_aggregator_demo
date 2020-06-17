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

// bucket size is 2 only for test purposes
$rowHandler = new HashMapRowAggregator(new DateHashCalculator(), 2);
$aggregator = new CsvMetricsReader($path, $rowHandler);

try {
    $aggregator->run();
    echo "Done! Check the result.csv\n";
} catch (CsvMetricsReaderException $e) {
    var_dump($e);
}
