# CSV metrics aggregator (demo task)

## Installation
> $ composer dump-autoload

## Usage
> $ php cli.php [dirToScan]

for example:
> $ php cli.php .

by this way it will scan the current directory. The output file will be named `result.csv`.

## Implementation details

- Does not require any external dependencies;
- Implemented on pure php;
- Based on **own implementation** of `HashMap` which stores on hard drive (with helping of `fseek` magic for O(1) access);
- Perfectly demonstrates pack/unpack php functions usage;
- As result, it does not consume extra memory while reading input data and generating result CVS file with aggregated data;
- Perfectly works with any size of HashMap bucket, supports automatic resolving of collisions (see HashMapRowAggregator);
- For customisation purposes see definitions (such as changing bucket size) of `HashMapRowAggregator` and `CsvMetricsReader` classes.

## References
* https://habr.com/ru/post/421179/

## Author
Andrey Vorobyev
