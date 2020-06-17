<?php
require_once 'vendor/autoload.php';

use CsvAggregator\CsvMetricsReader;
use CSVAggregator\CsvMetricsReaderException;
use CsvAggregator\HashMapRowAggregator;

$path = $argv[1];
if(empty($path)){
    echo "You must provide the directory path to scan!\n";
    exit(0);
}

$aggregator = new CsvMetricsReader($path, new HashMapRowAggregator());

try {
    $aggregator->run();
    echo "Done! Check the result.csv\n";
} catch (CsvMetricsReaderException $e) {
    var_dump($e);
}
