<?php

namespace CsvAggregator;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class CsvMetricsReader
{
    /**
     * @var string
     */
    private $directoryPath;

    /**
     * @var string
     */
    private $delimiter;

    /**
     * @var string
     */
    private $desiredFileExtension;

    /**
     * @var string
     */
    private $firstColumnName;

    /**
     * @var RowHandlerInterface
     */
    private $rowHandler;
    /**
     * @var int|string
     */
    private $requiredMetricColumnNum;

    /**
     * CsvAggregator constructor.
     * @param string $directoryPath
     * @param RowHandlerInterface $rowHandler
     * @param string|null $delimiter
     * @param string $desiredFileExtension
     * @param string $firstColumnName
     * @param int $requiredMetricColumnNum
     */
    public function __construct(
        string $directoryPath,
        RowHandlerInterface $rowHandler,
        string $delimiter = ';',
        string $desiredFileExtension = 'csv',
        string $firstColumnName = 'date',
        int $requiredMetricColumnNum = 3
    ) {
        $this->directoryPath = $directoryPath;
        $this->delimiter = $delimiter;
        $this->desiredFileExtension = $desiredFileExtension;
        $this->firstColumnName = $firstColumnName;
        $this->rowHandler = $rowHandler;
        $this->requiredMetricColumnNum = $requiredMetricColumnNum;
    }

    /**
     * @throws CsvMetricsReaderException
     */
    public function run(): void
    {
        $this->rowHandler->init();

        try {
            $directoryIterator = new RecursiveDirectoryIterator($this->directoryPath, FilesystemIterator::SKIP_DOTS);
        } catch (\Exception $e) {
            throw new CsvMetricsReaderException('Provided directory path is incorrect!');
        }

        $iterator = new RecursiveIteratorIterator(
            $directoryIterator,
            RecursiveIteratorIterator::SELF_FIRST,
            RecursiveIteratorIterator::CATCH_GET_CHILD
        );

        foreach ($iterator as $path => $fileInfo) {

            if ($fileInfo->getExtension() !== $this->desiredFileExtension) {
                $iterator->next();
                continue;
            }

            $this->handleFile($path);
        }

        $this->rowHandler->close();
    }

    protected function handleFile(string $filePath): void
    {
        $pointer = fopen($filePath, 'rb');

        $detectedColumns = [];
        $rowNumber = 0;

        // read line by line - do not waste the memory
        while ($currentLine = fgetcsv($pointer, 0, $this->delimiter)) {
            $rowNumber++;

            $dateValue = array_shift($currentLine);

            if ($rowNumber === 1) {
                // the file has wrong header, log it?
                if ($dateValue !== $this->firstColumnName) {
                    break;
                }

                // file has wrong columns number? (by task)
                if (count($currentLine) !== $this->requiredMetricColumnNum) {
                    break;
                }

                // save detected columns from the csv header
                // let's use arrow function in php 7.4!
                $detectedColumns =
                    array_map(
                        static function ($v) {
                            return trim($v);
                        },
                        $currentLine
                    );

                $this->rowHandler->setColumnNames(array_merge([$dateValue], $detectedColumns));

                continue;
            }

            $metricValues = [];
            foreach ($detectedColumns as $i => $columnName) {
                // set zero if csv is corrupted and value for the requested row does not exist at all
                if (!isset($currentLine[$i])) {
                    $currentValue = 0;
                } else {
                    $currentValue = trim($currentLine[$i]);

                    if (!is_numeric($currentValue)) {
                        // ignore not numeric values, set to zero by default
                        $currentValue = 0;
                    } else {
                        $currentValue = (float)$currentValue;
                    }
                }

                if (!is_numeric($currentValue)) {
                    // ignore not numeric values, set to zero by default
                    $currentValue = 0;
                }

                $metricValues[] = $currentValue;
            }

            $this->rowHandler->handleRow($dateValue, $metricValues);
        }

        fclose($pointer);
    }
}
