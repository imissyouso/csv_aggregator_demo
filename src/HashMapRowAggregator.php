<?php

namespace CsvAggregator;

class HashMapRowAggregator implements RowHandlerInterface
{
    private $tmpPointer;

    /**
     * @var int
     */
    private $hashMapBucketSize;

    /**
     * @var string
     */
    private $resultFilePath;

    /**
     * @var string
     */
    private $tmpFilePath;

    /**
     * @var float|int
     */
    private $currentTmpFileSize;

    /**
     * @var int
     */
    private $metricValuesPerRow;

    /**
     * @var array
     */
    private $columnNames;
    /**
     * @var string
     */
    private $resultFileDelimiter;

    /**
     * HashMapRowAggregator constructor.
     * @param int $hashMapBucketSize
     * @param string $resultFilePath
     * @param string $tmpFilePath
     * @param int $metricValuesPerRow
     * @param string $resultFileDelimiter
     */
    public function __construct(
        $hashMapBucketSize = 1024,
        $resultFilePath = './result.csv',
        $tmpFilePath = './out.bin',
        $metricValuesPerRow = 3,
        $resultFileDelimiter = ';'
    ) {
        $this->hashMapBucketSize = $hashMapBucketSize;
        $this->resultFilePath = $resultFilePath;
        $this->tmpFilePath = $tmpFilePath;
        $this->metricValuesPerRow = $metricValuesPerRow;
        $this->currentTmpFileSize = $hashMapBucketSize * $this->getChunkSize();
        $this->resultFileDelimiter = $resultFileDelimiter;
    }

    protected function getChunkSize(): int
    {
        // @TODO: should't be hardcoded ofc.
        // every chunk has ROW_HASH(4b)|...AGGREGATED_VALUES(4b*n)|LINK_TO_THE_COLLISIONAL_CHUNK(4b) structure in sum it gives us 20 bytes
        return 4 + 4 + ($this->metricValuesPerRow * 4);
    }

    protected function getRowNameHash(string $rowName): int
    {
        return (integer) str_replace('-', '', $rowName);
        //return (integer)md5($rowName); or any other hash function...
    }

    public function init(): void
    {
        $this->tmpPointer = fopen($this->tmpFilePath, 'wb+');
        // Populate new empty bucket with zeros
        fwrite($this->tmpPointer, str_repeat("\0", $this->currentTmpFileSize));
    }

    public function handleRow(string $rowName, array $rowData): void
    {
        $rowNameHash = $this->getRowNameHash($rowName);

        // have any questions? See https://habr.com/ru/post/421179/
        $bucketOffset = ($rowNameHash & ($this->hashMapBucketSize - 1)) * $this->getChunkSize();

        $this->put($bucketOffset, $rowNameHash, $rowData);
    }

    protected function parseChunk(string $chunk): array
    {
        // every chunk has ROW_HASH|...AGGREGATED_VALUES|LINK_TO_THE_COLLISIONAL_CHUNK structure in sum it gives us 20 bytes
        $parsedRowNameHash = unpack('I', $chunk)[1];
        $parsedValues = array_values(unpack('f3', $chunk, 4));
        $parsedLinkToTheNextChunk = unpack('L', $chunk, $this->getChunkSize() - 4)[1];

        return [$parsedRowNameHash, $parsedValues, $parsedLinkToTheNextChunk];
    }

    protected function put($offset, $rowNameHash, $values): void
    {
        fseek($this->tmpPointer, $offset, SEEK_SET);

        $currentChunk = fread($this->tmpPointer, $this->getChunkSize());

        [$parsedRowNameHash, $parsedValues, $parsedLinkToTheNextChunk] = $this->parseChunk($currentChunk);

        // If the place is already filled and has a link to another place in memory then jump there and try to save again
        if ($parsedLinkToTheNextChunk) {
            $this->put($parsedLinkToTheNextChunk, $parsedRowNameHash, $values);

            return;
        }

        if (!$parsedRowNameHash || $parsedRowNameHash === $rowNameHash) {
            // Aggregate values here
            foreach ($parsedValues as $i => $parsedValue) {
                $parsedValues[$i] += $values[$i];
            }

            fseek($this->tmpPointer, $offset, SEEK_SET);

            fwrite($this->tmpPointer, pack('I', $rowNameHash), 4);
            fwrite($this->tmpPointer, pack('f3', ...$parsedValues), count($parsedValues) * 4);

            return;
        }

        // Collision detected! Let's resolve this shit! See https://habr.com/ru/post/421179/
        if ($parsedRowNameHash !== $rowNameHash) {
            fseek($this->tmpPointer, ($offset + $this->getChunkSize()) - 4, SEEK_SET);

            fwrite($this->tmpPointer, pack('L', $this->currentTmpFileSize), 4);

            fseek($this->tmpPointer, 0, SEEK_END);

            fwrite($this->tmpPointer, str_repeat("\0", $this->getChunkSize()));

            $newOffset = $this->currentTmpFileSize;
            $this->currentTmpFileSize += $this->getChunkSize();

            $this->put($newOffset, $rowNameHash, $values);
        }
    }

    public function close(): void
    {
        // convert tmp file into the real CSV
        rewind($this->tmpPointer);
        $outPointer = fopen($this->resultFilePath, 'wb+');

        if(count($this->columnNames)){
            fputcsv(
                $outPointer,
                $this->columnNames,
                $this->resultFileDelimiter);
        }

        while ($currentChunk = fread($this->tmpPointer, $this->getChunkSize())) {
            [$parsedRowNameHash, $parsedValues] = $this->parseChunk($currentChunk);

            if ($parsedRowNameHash) {
                fputcsv(
                    $outPointer,
                    array_merge(
                        [$parsedRowNameHash],
                        array_map(
                            static function ($v) {
                                return round($v, 2);
                            },
                            $parsedValues
                        )
                    ),
                    $this->resultFileDelimiter
                );
            }
        }
        fclose($this->tmpPointer);
        fclose($outPointer);
        unlink($this->tmpFilePath);
    }

    public function setColumnNames(array $names): void
    {
        $this->columnNames = $names;
    }
}
