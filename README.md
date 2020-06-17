# CSV metrics aggregator (demo task)

## Installation
> $ composer dump-autoload

## Usage
> $ php cli.php [dirToScan]

for example:
> $ php cli.php data

by this way it will scan the `data` directory. The output file will be named `result.csv`.

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

## Comments
- For similar purposes in production, better to use something like `Kafka Streams`/`KSql` or `Redis Streams`.
- Ребят, ну тесты уже писать не стал, уже и так перебор :)
- p.s. если прям сильно нужно, то добавлю.
- здесь нет ни строчки чужого кода.
- всегда рад обсудить результат лично!

## Original task
https://gist.github.com/pavelkdev/435244a2c2e3a9d8dcb2353511fd9dad

## Author
Andrey Vorobyev
