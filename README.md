# Benchmarks for Tarantool PHP connectors

# Requirements

 * PHP 7.1+ (NTS and ZTS)
 * [Composer](https://getcomposer.org/)
 * [Tarantool](https://www.tarantool.io/) 1.7.1+
 * [ext-msgpack](https://github.com/msgpack/msgpack-php) to benchmark the msgpack pecl extension
 * ext-async ([fork](https://github.com/dreamsxin/ext-async)) to benchmark connectors in async mode
 * [ext-swoole](https://github.com/swoole/swoole-src) to benchmark connectors in async mode
 * [ext-tarantool](https://github.com/tarantool/tarantool-php) to benchmark the official PHP connector


## Usage

First, make sure you have the [bench.lua](bench.lua) instance running and listening on `localhost` on port `3301`.

> If you want to run it on Docker, execute:
>
> ```bash
> docker run -d --network host --name=tarantool-bench \
>    -v $PWD/bench.lua:/bench.lua tarantool/tarantool:2 tarantool /bench.lua
> ```

Then run

```bash
make
```

to see the usage text and a list of all available benchmarks. For example, to (re)run all benchmarks, execute

```bash
make clean bench-all
```

You may change default benchmark settings by defining the following environment variables:

* `TNT_BENCH_ITERATIONS`
* `TNT_BENCH_REVOLUTIONS`
* `TNT_BENCH_RETRY_THRESHOLD`

For example:

```bash
make clean bench-all TNT_BENCH_REVOLUTIONS=20000 TNT_BENCH_RETRY_THRESHOLD=5 
```


## Results

The below results were made running benchmarks on Apple MacBook Pro (2015) on the following environment: 

 * Linux Fedora 30
 * Tarantool 2.3.0-115-g5ba5ed37e running on Docker
 * Docker 19.03.3, build a872fc2f86
 * PHP 7.3.10 (cli) (built: Sep 24 2019 09:20:18) ( NTS )
 * PHP 7.3.10 (cli) (built: Sep 24 2019 09:20:18) ( ZTS )
 * tarantool/client 0.6.0-dev-61c7328
 * ext-msgpack 2.0.3
 * ext-async 0.3.0
 * ext-swoole 4.4.7
 * ext-parallel 1.1.4-dev
 * ext-tarantool 0.3.2 with the [patch](https://github.com/tarantool/tarantool-php/pull/148/files) 

#### bench-sync-connectors
#### bench-sync-client-packers
#### bench-sync-client-protocols
#### bench-async-coroutines
#### bench-async-connectors
#### bench-async-client-protocols
#### bench-swoole-coroutines
#### bench-swoole-connectors
#### bench-swoole-client-protocols
#### bench-parallel-threads
#### bench-parallel-connectors
#### bench-parallel-client-protocols
#### bench-extensions


## License

The library is released under the MIT License. See the bundled [LICENSE](LICENSE) file for details.
