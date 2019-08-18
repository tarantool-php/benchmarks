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

    bench-sync
        $(clr_comment)Benchmark connectors in sync mode$(clr_reset)

    bench-sync-client-packers
        $(clr_comment)Benchmark client packers$(clr_reset)

    bench-async
        $(clr_comment)Benchmark connectors in async mode using $(clr_info)ext-async$(clr_reset)

    bench-async-coroutines
        $(clr_comment)Benchmark connectors in async mode using $(clr_info)ext-async$(clr_comment) with different number of parallel coroutines$(clr_reset)

    bench-swoole
        $(clr_comment)Benchmark connectors in async mode using $(clr_info)ext-swoole$(clr_reset)

    bench-swoole-coroutines
        $(clr_comment)Benchmark connectors in async mode using $(clr_info)ext-swoole$(clr_comment) with different number of parallel coroutines$(clr_reset)

    bench-async-vs-swoole
        $(clr_comment)Benchmark async extensions$(clr_reset)
endef


.PHONY: help
help::
	@echo $(info $(help))

reports:
	@mkdir reports

vendor: reports
	@composer install

.PHONY: clean
clean:
	@find reports -type f -not -name '_*' -delete

.PHONY: bench-sync
bench-sync: vendor
	@vendor/bin/phpbench run benchmarks/TarantoolBench.php --dump-file=reports/sync__tarantool.xml
	@TNT_BENCH_PACKER_TYPE=pecl vendor/bin/phpbench run benchmarks/ClientBench.php --dump-file=reports/sync__client_packer_pecl.xml
	@TNT_BENCH_PACKER_TYPE=pecl vendor/bin/phpbench run benchmarks/ClientHandlerBench.php --dump-file=reports/sync__client_handler_packer_pecl.xml
	@vendor/bin/phpbench report \
		--file=reports/sync__tarantool.xml \
		--file=reports/sync__client_packer_pecl.xml \
		--file=reports/sync__client_handler_packer_pecl.xml \
		--report=tag-table
	@vendor/bin/phpbench report \
		--file=reports/sync__tarantool.xml \
		--file=reports/sync__client_packer_pecl.xml \
		--file=reports/sync__client_handler_packer_pecl.xml \
		--report=chart --output='extends: "chart-image", basename: "sync"'

.PHONY: bench-sync-client-packers
bench-sync-client-packers: vendor
	@TNT_BENCH_PACKER_TYPE=pecl vendor/bin/phpbench run benchmarks/ClientBench.php --dump-file=reports/sync_client_packers__packer_pecl.xml --tag=pecl_packer
	@TNT_BENCH_PACKER_TYPE=pure vendor/bin/phpbench run benchmarks/ClientBench.php --dump-file=reports/sync_client_packers__packer_pure.xml --tag=pure_packer
	@TNT_BENCH_PACKER_TYPE=pecl vendor/bin/phpbench run benchmarks/ClientHandlerBench.php --dump-file=reports/sync_client_packers__handler_packer_pecl.xml --tag=pecl_packer
	@TNT_BENCH_PACKER_TYPE=pure vendor/bin/phpbench run benchmarks/ClientHandlerBench.php --dump-file=reports/sync_client_packers__handler_packer_pure.xml --tag=pure_packer
	@vendor/bin/phpbench report \
		--file=reports/sync_client_packers__packer_pecl.xml \
		--file=reports/sync_client_packers__packer_pure.xml \
		--file=reports/sync_client_packers__handler_packer_pecl.xml \
		--file=reports/sync_client_packers__handler_packer_pure.xml \
		--report=tag-table
	@vendor/bin/phpbench report \
		--file=reports/sync_client_packers__packer_pecl.xml \
		--file=reports/sync_client_packers__packer_pure.xml \
		--file=reports/sync_client_packers__handler_packer_pecl.xml \
		--file=reports/sync_client_packers__handler_packer_pure.xml \
		--report=chart --output='extends: "chart-image", basename: "sync-client-packers"'

.PHONY: bench-async
bench-async: vendor
	@cd benchmarks/Async && TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientBench.php --dump-file=../../reports/async__client_packer_pecl.xml
	@cd benchmarks/Async && TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientHandlerBench.php --dump-file=../../reports/async__client_handler_packer_pecl.xml
	@cd benchmarks/Async && ../../vendor/bin/phpbench report \
		--file=../../reports/async__client_packer_pecl.xml \
		--file=../../reports/async__client_handler_packer_pecl.xml \
		--report=tag-table
	@cd benchmarks/Async && ../../vendor/bin/phpbench report \
		--file=../../reports/async__client_packer_pecl.xml \
		--file=../../reports/async__client_handler_packer_pecl.xml \
		--report=chart --output='extends: "chart-image", basename: "async"'

.PHONY: bench-async-coroutines
bench-async-coroutines: vendor
	@cd benchmarks/Async && TNT_BENCH_ASYNC_COROUTINES=10 TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientBench.php --dump-file=../../reports/async_coroutines__client_packer_pecl_co10.xml --tag=ten
	@cd benchmarks/Async && TNT_BENCH_ASYNC_COROUTINES=25 TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientBench.php --dump-file=../../reports/async_coroutines__client_packer_pecl_co25.xml --tag=twenty_five
	@cd benchmarks/Async && TNT_BENCH_ASYNC_COROUTINES=50 TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientBench.php --dump-file=../../reports/async_coroutines__client_packer_pecl_co50.xml --tag=fifty
	@cd benchmarks/Async && TNT_BENCH_ASYNC_COROUTINES=100 TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientBench.php --dump-file=../../reports/async_coroutines__client_packer_pecl_co100.xml --tag=hundred
	@cd benchmarks/Async && TNT_BENCH_ASYNC_COROUTINES=200 TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientBench.php --dump-file=../../reports/async_coroutines__client_packer_pecl_co200.xml --tag=two_hundred
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
		--report=chart --output='extends: "chart-image", basename: "async-coroutines"'

.PHONY: bench-swoole
bench-swoole: vendor
	@cd benchmarks/Swoole && ../../vendor/bin/phpbench run ../TarantoolBench.php --dump-file=../../reports/swoole__tarantool.xml
	@cd benchmarks/Swoole && TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientBench.php --dump-file=../../reports/swoole__client_packer_pecl.xml
	@cd benchmarks/Swoole && TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientHandlerBench.php --dump-file=../../reports/swoole__client_handler_packer_pecl.xml
	@cd benchmarks/Swoole && ../../vendor/bin/phpbench report \
		--file=../../reports/swoole__tarantool.xml \
		--file=../../reports/swoole__client_packer_pecl.xml \
		--file=../../reports/swoole__client_handler_packer_pecl.xml \
		--report=tag-table
	@cd benchmarks/Swoole && ../../vendor/bin/phpbench report \
		--file=../../reports/swoole__tarantool.xml \
		--file=../../reports/swoole__client_packer_pecl.xml \
		--file=../../reports/swoole__client_handler_packer_pecl.xml \
		--report=chart --output='extends: "chart-image", basename: "swoole"'

.PHONY: bench-swoole-coroutines
bench-swoole-coroutines: vendor
	@cd benchmarks/Swoole && TNT_BENCH_ASYNC_COROUTINES=10 TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientBench.php --dump-file=../../reports/swoole_coroutines__client_packer_pecl_co10.xml --tag=ten
	@cd benchmarks/Swoole && TNT_BENCH_ASYNC_COROUTINES=25 TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientBench.php --dump-file=../../reports/swoole_coroutines__client_packer_pecl_co25.xml --tag=twenty_five
	@cd benchmarks/Swoole && TNT_BENCH_ASYNC_COROUTINES=50 TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientBench.php --dump-file=../../reports/swoole_coroutines__client_packer_pecl_co50.xml --tag=fifty
	@cd benchmarks/Swoole && TNT_BENCH_ASYNC_COROUTINES=100 TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientBench.php --dump-file=../../reports/swoole_coroutines__client_packer_pecl_co100.xml --tag=hundred
	@cd benchmarks/Swoole && TNT_BENCH_ASYNC_COROUTINES=200 TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientBench.php --dump-file=../../reports/swoole_coroutines__client_packer_pecl_co200.xml --tag=two_hundred
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
		--report=chart --output='extends: "chart-image", basename: "swoole-coroutines"'

.PHONY: bench-async-vs-swoole
bench-async-vs-swoole: vendor
	@cd benchmarks/Async && TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientBench.php --dump-file=../../reports/async_vs_swoole__async_client_packer_pecl.xml --tag=async
	@cd benchmarks/Swoole && TNT_BENCH_PACKER_TYPE=pecl ../../vendor/bin/phpbench run ../ClientBench.php --dump-file=../../reports/async_vs_swoole__swoole_client_packer_pecl.xml --tag=swoole
	@vendor/bin/phpbench report \
		--file=reports/async_vs_swoole__async_client_packer_pecl.xml \
		--file=reports/async_vs_swoole__swoole_client_packer_pecl.xml \
		--report=tag-table
	@vendor/bin/phpbench report \
		--file=reports/async_vs_swoole__async_client_packer_pecl.xml \
		--file=reports/async_vs_swoole__swoole_client_packer_pecl.xml \
		--report=chart --output='extends: "chart-image", basename: "async-vs-swoole"'
