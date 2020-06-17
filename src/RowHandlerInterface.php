<?php

namespace CsvAggregator;

interface RowHandlerInterface {
    public function init();
    public function handleRow(string $rowName, array $rowData);
    public function close();
    public function setColumnNames(array $names);
}
