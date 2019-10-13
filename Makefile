SHELL := $(shell which bash)

clr_error := $(shell tty -s > /dev/null 2>&1 && tput setaf 1)
clr_comment := $(shell tty -s > /dev/null 2>&1 && tput setaf 2)
clr_info := $(shell tty -s > /dev/null 2>&1 && tput setaf 3)
clr_reset := $(shell tty -s > /dev/null 2>&1 && tput sgr0)

define help
$(clr_info)Usage:$(clr_reset)
    make [target]

$(clr_info)Targets:$(clr_reset)
    clean
        $(clr_comment)Remove previously generated reports (if any)$(clr_reset)

    bench-all
        $(clr_comment)Run all benchmarks$(clr_reset)

    bench-sync
        $(clr_comment)Benchmark connectors in sync mode$(clr_reset)

    bench-sync-client-packers
        $(clr_comment)Benchmark client packers in sync mode$(clr_reset)

    bench-sync-client-protocols
        $(clr_comment)Benchmark binary/SQL protocols in sync mode$(clr_reset)

    bench-async
        $(clr_comment)Benchmark connectors in async mode using $(clr_info)ext-async$(clr_reset)

    bench-async-coroutines
        $(clr_comment)Benchmark connectors in async mode using $(clr_info)ext-async$(clr_comment) with different number of coroutines$(clr_reset)

    bench-async-client-protocols
        $(clr_comment)Benchmark binary/SQL protocols in async mode using $(clr_info)ext-async$(clr_reset)

    bench-swoole
        $(clr_comment)Benchmark connectors in async mode using $(clr_info)ext-swoole$(clr_reset)

    bench-swoole-coroutines
        $(clr_comment)Benchmark connectors in async mode using $(clr_info)ext-swoole$(clr_comment) with different number of coroutines$(clr_reset)

    bench-swoole-client-protocols
        $(clr_comment)Benchmark binary/SQL protocols in async mode using $(clr_info)ext-swoole$(clr_reset)

    bench-parallel
        $(clr_comment)Benchmark connectors in parallel mode using $(clr_info)ext-parallel$(clr_reset)

    bench-parallel-threads
        $(clr_comment)Benchmark connectors in parallel mode using $(clr_info)ext-parallel$(clr_comment) with different number of threads$(clr_reset)

    bench-parallel-client-protocols
        $(clr_comment)Benchmark binary/SQL protocols in parallel mode using $(clr_info)ext-parallel$(clr_reset)

    bench-extensions
        $(clr_comment)Benchmark pecl extensions$(clr_reset)

    bench-parallel-with-async
        $(clr_comment)Benchmark connectors using both $(clr_info)ext-parallel$(clr_comment) and $(clr_info)ext-async$(clr_reset)
endef


.PHONY: help
help:
	@echo $(info $(help))

reports:
	@mkdir reports

vendor: reports
	@composer install

.PHONY: clean
clean:
	@find reports -type f -not -name '_*' -delete

.PHONY: bench-all
bench-all: \
	bench-sync \
	bench-sync-client-packers \
	bench-sync-client-protocols \
	bench-async \
	bench-async-coroutines \
	bench-async-client-protocols \
	bench-swoole \
	bench-swoole-coroutines \
	bench-swoole-client-protocols \
	bench-parallel \
	bench-parallel-threads \
	bench-parallel-client-protocols \
	bench-extensions \
	bench-parallel-with-async


.PHONY: bench-sync
bench-sync: vendor
	@vendor/bin/phpbench run benchmarks/TarantoolBench.php --dump-file=reports/sync__tarantool.xml
	@TNT_BENCH_PACKER_TYPE=pecl vendor/bin/phpbench run benchmarks/ClientBench.php --dump-file=reports/sync__client_packer_pecl.xml
	@vendor/bin/phpbench report \
		--file=reports/sync__tarantool.xml \
		--file=reports/sync__client_packer_pecl.xml \
		--report=tag-table
	@vendor/bin/phpbench report \
		--file=reports/sync__tarantool.xml \
		--file=reports/sync__client_packer_pecl.xml \
		--report=chart --output='extends: "chart-image", basename: "sync"'

.PHONY: bench-sync-client-packers
bench-sync-client-packers: vendor
	@TNT_BENCH_PACKER_TYPE=pecl vendor/bin/phpbench run benchmarks/ClientBench.php --dump-file=reports/sync_client_packers__packer_pecl.xml --tag=pecl_packer
	@TNT_BENCH_PACKER_TYPE=pure vendor/bin/phpbench run benchmarks/ClientBench.php --dump-file=reports/sync_client_packers__packer_pure.xml --tag=pure_packer
	@vendor/bin/phpbench report \
		--file=reports/sync_client_packers__packer_pecl.xml \
		--file=reports/sync_client_packers__packer_pure.xml \
		--report=tag-table
	@vendor/bin/phpbench report \
		--file=reports/sync_client_packers__packer_pecl.xml \
		--file=reports/sync_client_packers__packer_pure.xml \
		--report=chart --output='extends: "chart-image", basename: "sync_client_packers"'

.PHONY: bench-sync-client-protocols
bench-sync-client-protocols: vendor
	@TNT_BENCH_PACKER_TYPE=pecl vendor/bin/phpbench run benchmarks/ClientBench.php --dump-file=reports/sync_client_protocols__binary_packer_pecl.xml --filter=select --filter=insert --filter=update --filter=delete
	@TNT_BENCH_PACKER_TYPE=pecl vendor/bin/phpbench run benchmarks/ClientSqlBench.php --dump-file=reports/sync_client_protocols__sql_packer_pecl.xml --filter=select --filter=insert --filter=update --filter=delete
	@vendor/bin/phpbench report \
		--file=reports/sync_client_protocols__binary_packer_pecl.xml \
		--file=reports/sync_client_protocols__sql_packer_pecl.xml \
		--report=tag-table
	@vendor/bin/phpbench report \
		--file=reports/sync_client_protocols__binary_packer_pecl.xml \
		--file=reports/sync_client_protocols__sql_packer_pecl.xml \
		--report=chart --output='extends: "chart-image", basename: "sync_client_protocols"'


.PHONY: bench-async
bench-async: vendor
	@cd benchmarks/Async && TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientBench.php --dump-file=../../reports/async__client_packer_pecl.xml
	@cd benchmarks/Async && ../../vendor/bin/phpbench report \
		--file=../../reports/async__client_packer_pecl.xml \
		--report=tag-table
	@cd benchmarks/Async && ../../vendor/bin/phpbench report \
		--file=../../reports/async__client_packer_pecl.xml \
		--report=chart --output='extends: "chart-image", basename: "async"'

.PHONY: bench-async-coroutines
bench-async-coroutines: vendor
	@cd benchmarks/Async && TNT_BENCH_COROUTINES=10 TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientBench.php --dump-file=../../reports/async_coroutines__client_packer_pecl_co10.xml --tag=co10
	@cd benchmarks/Async && TNT_BENCH_COROUTINES=25 TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientBench.php --dump-file=../../reports/async_coroutines__client_packer_pecl_co25.xml --tag=co25
	@cd benchmarks/Async && TNT_BENCH_COROUTINES=50 TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientBench.php --dump-file=../../reports/async_coroutines__client_packer_pecl_co50.xml --tag=co50
	@cd benchmarks/Async && TNT_BENCH_COROUTINES=100 TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientBench.php --dump-file=../../reports/async_coroutines__client_packer_pecl_co100.xml --tag=co100
	@cd benchmarks/Async && TNT_BENCH_COROUTINES=200 TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientBench.php --dump-file=../../reports/async_coroutines__client_packer_pecl_co200.xml --tag=co200
	@cd benchmarks/Async && ../../vendor/bin/phpbench report \
		--file=../../reports/async_coroutines__client_packer_pecl_co10.xml \
		--file=../../reports/async_coroutines__client_packer_pecl_co25.xml \
		--file=../../reports/async_coroutines__client_packer_pecl_co50.xml \
		--file=../../reports/async_coroutines__client_packer_pecl_co100.xml \
		--file=../../reports/async_coroutines__client_packer_pecl_co200.xml \
		--report=tag-table
	@cd benchmarks/Async && ../../vendor/bin/phpbench report \
		--file=../../reports/async_coroutines__client_packer_pecl_co10.xml \
		--file=../../reports/async_coroutines__client_packer_pecl_co25.xml \
		--file=../../reports/async_coroutines__client_packer_pecl_co50.xml \
		--file=../../reports/async_coroutines__client_packer_pecl_co100.xml \
		--file=../../reports/async_coroutines__client_packer_pecl_co200.xml \
		--report=chart --output='extends: "chart-image", basename: "async_coroutines"'

.PHONY: bench-async-client-protocols
bench-async-client-protocols: vendor
	@cd benchmarks/Async && TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientBench.php --dump-file=../../reports/async_client_protocols__binary_packer_pecl.xml --filter=select --filter=insert --filter=update --filter=delete
	@cd benchmarks/Async && TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientSqlBench.php --dump-file=../../reports/async_client_protocols__sql_packer_pecl.xml --filter=select --filter=insert --filter=update --filter=delete
	@cd benchmarks/Async && ../../vendor/bin/phpbench report \
		--file=../../reports/async_client_protocols__binary_packer_pecl.xml \
		--file=../../reports/async_client_protocols__sql_packer_pecl.xml \
		--report=tag-table
	@cd benchmarks/Async && ../../vendor/bin/phpbench report \
		--file=../../reports/async_client_protocols__binary_packer_pecl.xml \
		--file=../../reports/async_client_protocols__sql_packer_pecl.xml \
		--report=chart --output='extends: "chart-image", basename: "async_client_protocols"'


.PHONY: bench-swoole
bench-swoole: vendor
	@cd benchmarks/Swoole && ../../vendor/bin/phpbench run ../TarantoolBench.php --dump-file=../../reports/swoole__tarantool.xml
	@cd benchmarks/Swoole && TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientBench.php --dump-file=../../reports/swoole__client_packer_pecl.xml
	@cd benchmarks/Swoole && ../../vendor/bin/phpbench report \
		--file=../../reports/swoole__tarantool.xml \
		--file=../../reports/swoole__client_packer_pecl.xml \
		--report=tag-table
	@cd benchmarks/Swoole && ../../vendor/bin/phpbench report \
		--file=../../reports/swoole__tarantool.xml \
		--file=../../reports/swoole__client_packer_pecl.xml \
		--report=chart --output='extends: "chart-image", basename: "swoole"'

.PHONY: bench-swoole-coroutines
bench-swoole-coroutines: vendor
	@cd benchmarks/Swoole && TNT_BENCH_COROUTINES=10 TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientBench.php --dump-file=../../reports/swoole_coroutines__client_packer_pecl_co10.xml --tag=co10
	@cd benchmarks/Swoole && TNT_BENCH_COROUTINES=25 TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientBench.php --dump-file=../../reports/swoole_coroutines__client_packer_pecl_co25.xml --tag=co25
	@cd benchmarks/Swoole && TNT_BENCH_COROUTINES=50 TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientBench.php --dump-file=../../reports/swoole_coroutines__client_packer_pecl_co50.xml --tag=co50
	@cd benchmarks/Swoole && TNT_BENCH_COROUTINES=100 TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientBench.php --dump-file=../../reports/swoole_coroutines__client_packer_pecl_co100.xml --tag=co100
	@cd benchmarks/Swoole && TNT_BENCH_COROUTINES=200 TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientBench.php --dump-file=../../reports/swoole_coroutines__client_packer_pecl_co200.xml --tag=co200
	@cd benchmarks/Swoole && ../../vendor/bin/phpbench report \
		--file=../../reports/swoole_coroutines__client_packer_pecl_co10.xml \
		--file=../../reports/swoole_coroutines__client_packer_pecl_co25.xml \
		--file=../../reports/swoole_coroutines__client_packer_pecl_co50.xml \
		--file=../../reports/swoole_coroutines__client_packer_pecl_co100.xml \
		--file=../../reports/swoole_coroutines__client_packer_pecl_co200.xml \
		--report=tag-table
	@cd benchmarks/Swoole && ../../vendor/bin/phpbench report \
		--file=../../reports/swoole_coroutines__client_packer_pecl_co10.xml \
		--file=../../reports/swoole_coroutines__client_packer_pecl_co25.xml \
		--file=../../reports/swoole_coroutines__client_packer_pecl_co50.xml \
		--file=../../reports/swoole_coroutines__client_packer_pecl_co100.xml \
		--file=../../reports/swoole_coroutines__client_packer_pecl_co200.xml \
		--report=chart --output='extends: "chart-image", basename: "swoole_coroutines"'

.PHONY: bench-swoole-client-protocols
bench-swoole-client-protocols: vendor
	@cd benchmarks/Swoole && TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientBench.php --dump-file=../../reports/swoole_client_protocols__binary_packer_pecl.xml --filter=select --filter=insert --filter=update --filter=delete
	@cd benchmarks/Swoole && TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientSqlBench.php --dump-file=../../reports/swoole_client_protocols__sql_packer_pecl.xml --filter=select --filter=insert --filter=update --filter=delete
	@cd benchmarks/Swoole && ../../vendor/bin/phpbench report \
		--file=../../reports/swoole_client_protocols__binary_packer_pecl.xml \
		--file=../../reports/swoole_client_protocols__sql_packer_pecl.xml \
		--report=tag-table
	@cd benchmarks/Swoole && ../../vendor/bin/phpbench report \
		--file=../../reports/swoole_client_protocols__binary_packer_pecl.xml \
		--file=../../reports/swoole_client_protocols__sql_packer_pecl.xml \
		--report=chart --output='extends: "chart-image", basename: "swoole_client_protocols"'


.PHONY: bench-parallel
bench-parallel: vendor
	@cd benchmarks/Parallel && TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientBench.php --dump-file=../../reports/parallel__client_packer_pecl.xml
	@cd benchmarks/Parallel && ../../vendor/bin/phpbench report \
		--file=../../reports/parallel__client_packer_pecl.xml \
		--report=tag-table
	@cd benchmarks/Parallel && ../../vendor/bin/phpbench report \
		--file=../../reports/parallel__client_packer_pecl.xml \
		--report=chart --output='extends: "chart-image", basename: "parallel"'

.PHONY: bench-parallel-threads
bench-parallel-threads: vendor
	@cd benchmarks/Parallel && TNT_BENCH_THREADS=2 TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientBench.php --dump-file=../../reports/parallel_threads__client_packer_pecl_t2.xml --tag=t2
	@cd benchmarks/Parallel && TNT_BENCH_THREADS=4 TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientBench.php --dump-file=../../reports/parallel_threads__client_packer_pecl_t4.xml --tag=t4
	@cd benchmarks/Parallel && TNT_BENCH_THREADS=8 TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientBench.php --dump-file=../../reports/parallel_threads__client_packer_pecl_t8.xml --tag=t8
	@cd benchmarks/Parallel && TNT_BENCH_THREADS=16 TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientBench.php --dump-file=../../reports/parallel_threads__client_packer_pecl_t16.xml --tag=t16
	@cd benchmarks/Parallel && TNT_BENCH_THREADS=32 TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientBench.php --dump-file=../../reports/parallel_threads__client_packer_pecl_t32.xml --tag=t32
	@cd benchmarks/Parallel && ../../vendor/bin/phpbench report \
		--file=../../reports/parallel_threads__client_packer_pecl_t2.xml \
		--file=../../reports/parallel_threads__client_packer_pecl_t4.xml \
		--file=../../reports/parallel_threads__client_packer_pecl_t8.xml \
		--file=../../reports/parallel_threads__client_packer_pecl_t16.xml \
		--file=../../reports/parallel_threads__client_packer_pecl_t32.xml \
		--report=tag-table
	@cd benchmarks/Parallel && ../../vendor/bin/phpbench report \
		--file=../../reports/parallel_threads__client_packer_pecl_t2.xml \
		--file=../../reports/parallel_threads__client_packer_pecl_t4.xml \
		--file=../../reports/parallel_threads__client_packer_pecl_t8.xml \
		--file=../../reports/parallel_threads__client_packer_pecl_t16.xml \
		--file=../../reports/parallel_threads__client_packer_pecl_t32.xml \
		--report=chart --output='extends: "chart-image", basename: "parallel_threads"'

.PHONY: bench-parallel-client-protocols
bench-parallel-client-protocols: vendor
	@cd benchmarks/Parallel && TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientBench.php --dump-file=../../reports/parallel_client_protocols__binary_packer_pecl.xml --filter=select --filter=insert --filter=update --filter=delete
	@cd benchmarks/Parallel && TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientSqlBench.php --dump-file=../../reports/parallel_client_protocols__sql_packer_pecl.xml --filter=select --filter=insert --filter=update --filter=delete
	@cd benchmarks/Parallel && ../../vendor/bin/phpbench report \
		--file=../../reports/parallel_client_protocols__binary_packer_pecl.xml \
		--file=../../reports/parallel_client_protocols__sql_packer_pecl.xml \
		--report=tag-table
	@cd benchmarks/Parallel && ../../vendor/bin/phpbench report \
		--file=../../reports/parallel_client_protocols__binary_packer_pecl.xml \
		--file=../../reports/parallel_client_protocols__sql_packer_pecl.xml \
		--report=chart --output='extends: "chart-image", basename: "parallel_client_protocols"'


.PHONY: bench-extensions
bench-extensions: vendor
	@cd benchmarks/Async && TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientBench.php --dump-file=../../reports/extensions__async_client_packer_pecl.xml --tag=async
	@cd benchmarks/Swoole && TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientBench.php --dump-file=../../reports/extensions__swoole_client_packer_pecl.xml --tag=swoole
	@cd benchmarks/Parallel && TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientBench.php --dump-file=../../reports/extensions__parallel_client_packer_pecl.xml --tag=parallel
	@vendor/bin/phpbench report \
		--file=reports/extensions__async_client_packer_pecl.xml \
		--file=reports/extensions__swoole_client_packer_pecl.xml \
		--file=reports/extensions__parallel_client_packer_pecl.xml \
		--report=tag-table
	@vendor/bin/phpbench report \
		--file=reports/extensions__async_client_packer_pecl.xml \
		--file=reports/extensions__swoole_client_packer_pecl.xml \
		--file=reports/extensions__parallel_client_packer_pecl.xml \
		--report=chart --output='extends: "chart-image", basename: "extensions"'


.PHONY: bench-parallel-with-async
bench-parallel-with-async: vendor
	@cd benchmarks/ParallelWithAsync && TNT_BENCH_THREADS=2 TNT_BENCH_COROUTINES=10 TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientBench.php --dump-file=../../reports/parallel_with_async__client_packer_pecl.xml
	@cd benchmarks/ParallelWithAsync && ../../vendor/bin/phpbench report \
		--file=../../reports/parallel_with_async__client_packer_pecl.xml \
		--report=tag-table
	@cd benchmarks/ParallelWithAsync && ../../vendor/bin/phpbench report \
		--file=../../reports/parallel_with_async__client_packer_pecl.xml \
		--report=chart --output='extends: "chart-image", basename: "parallel_with_async"'
