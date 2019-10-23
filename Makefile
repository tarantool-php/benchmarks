clr_error := $(shell tty -s > /dev/null 2>&1 && tput setaf 1)
clr_comment := $(shell tty -s > /dev/null 2>&1 && tput setaf 2)
clr_info := $(shell tty -s > /dev/null 2>&1 && tput setaf 3)
clr_reset := $(shell tty -s > /dev/null 2>&1 && tput sgr0)
comma := ,

define help
$(clr_info)Usage:$(clr_reset)
    make [target]

$(clr_info)Targets:$(clr_reset)
    clean
        $(clr_comment)Remove previously generated reports (if any)$(clr_reset)

    bench-all
        $(clr_comment)Run all benchmarks$(clr_reset)

    bench-sync-connectors
        $(clr_comment)Benchmark connectors in sync mode$(clr_reset)

    bench-sync-client-packers
        $(clr_comment)Benchmark client packers in sync mode$(clr_reset)

    bench-sync-client-protocols
        $(clr_comment)Benchmark binary/SQL protocols in sync mode$(clr_reset)

    bench-async-coroutines
        $(clr_comment)Benchmark connectors in async mode using $(clr_info)ext-async$(clr_comment) with different number of coroutines$(clr_reset)

    bench-async-connectors
        $(clr_comment)Benchmark connectors in async mode using $(clr_info)ext-async$(clr_reset)

    bench-async-client-protocols
        $(clr_comment)Benchmark binary/SQL protocols in async mode using $(clr_info)ext-async$(clr_reset)

    bench-swoole-coroutines
        $(clr_comment)Benchmark connectors in async mode using $(clr_info)ext-swoole$(clr_comment) with different number of coroutines$(clr_reset)

    bench-swoole-connectors
        $(clr_comment)Benchmark connectors in async mode using $(clr_info)ext-swoole$(clr_reset)

    bench-swoole-client-protocols
        $(clr_comment)Benchmark binary/SQL protocols in async mode using $(clr_info)ext-swoole$(clr_reset)

    bench-parallel-threads
        $(clr_comment)Benchmark connectors in parallel mode using $(clr_info)ext-parallel$(clr_comment) with different number of threads$(clr_reset)

    bench-parallel-connectors
        $(clr_comment)Benchmark connectors in parallel mode using $(clr_info)ext-parallel$(clr_reset)

    bench-parallel-client-protocols
        $(clr_comment)Benchmark binary/SQL protocols in parallel mode using $(clr_info)ext-parallel$(clr_reset)

    bench-extensions
        $(clr_comment)Benchmark pecl extensions$(clr_reset)
endef

define run_bench
    cd benchmarks/$(1) && ../../vendor/bin/phpbench run ../$(2)Bench.php --dump-file=../../$@ --tag=$(subst reports/,,$(subst .xml,,$@)) $(phpbench_options) $(3) || true
endef

define make_report
    cd benchmarks/$(1) && ../../vendor/bin/phpbench report --file=../../$(subst .xml ,.xml --file=../../,$^) --report=tag-table
    cd benchmarks/$(1) && ../../vendor/bin/phpbench report --file=../../$(subst .xml ,.xml --file=../../,$^) --report=chart --output='extends: "chart-image", basename: "$(subst -,_,$(subst bench-,,$@))", "labels": {$(2)}'
endef


.PHONY: help
help:
	@echo $(info $(help))

vendor:
	@composer install

reports/%: phpbench_options += $(if $(TNT_BENCH_ITERATIONS), --iterations=$(TNT_BENCH_ITERATIONS),)
reports/%: phpbench_options += $(if $(TNT_BENCH_REVOLUTIONS), --revs=$(TNT_BENCH_REVOLUTIONS),)
reports/%: phpbench_options += $(if $(TNT_BENCH_RETRY_THRESHOLD), --retry-threshold=$(TNT_BENCH_RETRY_THRESHOLD),)


.PHONY: clean
clean:
	@find reports -type f -not -name '_*' -delete

.PHONY: bench-all
bench-all: \
	bench-sync-connectors \
	bench-sync-client-packers \
	bench-sync-client-protocols \
	bench-async-coroutines \
	bench-async-connectors \
	bench-async-client-protocols \
	bench-swoole-coroutines \
	bench-swoole-connectors \
	bench-swoole-client-protocols \
	bench-parallel-threads \
	bench-parallel-connectors \
	bench-parallel-client-protocols \
	bench-extensions


# sync

reports/sync__tarantool.xml: vendor
	@$(call run_bench,Sync,Tarantool)

reports/sync__client__packer_pecl.xml: vendor
	@export TNT_BENCH_PACKER=pecl && $(call run_bench,Sync,Client)

reports/sync__client__packer_pure.xml: vendor
	@export TNT_BENCH_PACKER=pure && $(call run_bench,Sync,Client)

reports/sync__client__packer_pecl__protocol_bin.xml: vendor
	@export TNT_BENCH_PACKER=pecl && $(call run_bench,Sync,Client,--filter=select --filter=insert --filter=update --filter=delete)

reports/sync__client__packer_pecl__protocol_sql.xml: vendor
	@export TNT_BENCH_PACKER=pecl && $(call run_bench,Sync,ClientSql,--filter=select --filter=insert --filter=update --filter=delete)

.PHONY: bench-sync-connectors
bench-sync-connectors: \
	reports/sync__tarantool.xml \
	reports/sync__client__packer_pecl.xml
	@$(call make_report,Sync, \
		"sync__tarantool": "Tarantool"$(comma) \
		"sync__client__packer_pecl": "Client" \
	)

.PHONY: bench-sync-client-packers
bench-sync-client-packers: \
	reports/sync__client__packer_pecl.xml \
	reports/sync__client__packer_pure.xml
	@$(call make_report,Sync, \
		"sync__client__packer_pecl": "Pecl"$(comma) \
		"sync__client__packer_pure": "Pure" \
	)

.PHONY: bench-sync-client-protocols
bench-sync-client-protocols: \
	reports/sync__client__packer_pecl__protocol_bin.xml \
	reports/sync__client__packer_pecl__protocol_sql.xml
	@$(call make_report,Sync, \
		"sync__client__packer_pecl__protocol_bin": "Binary"$(comma) \
		"sync__client__packer_pecl__protocol_sql": "Sql" \
	)


# async

reports/async__tarantool__co%.xml: vendor
	@export TNT_BENCH_COROUTINES=$* && $(call run_bench,Async,Tarantool)

reports/async__client__packer_pecl__co%.xml: vendor
	@export TNT_BENCH_PACKER=pecl TNT_BENCH_COROUTINES=$* && $(call run_bench,Async,Client)

reports/async__client__packer_pecl__protocol_bin__co%.xml: vendor
	@export TNT_BENCH_PACKER=pecl TNT_BENCH_COROUTINES=$* && $(call run_bench,Async,Client,--filter=select --filter=insert --filter=update --filter=delete)

reports/async__client__packer_pecl__protocol_sql__co%.xml: vendor
	@export TNT_BENCH_PACKER=pecl TNT_BENCH_COROUTINES=$* && $(call run_bench,Async,ClientSql,--filter=select --filter=insert --filter=update --filter=delete)

.PHONY: bench-async-coroutines
bench-async-coroutines: \
	reports/async__client__packer_pecl__co10.xml \
	reports/async__client__packer_pecl__co25.xml \
	reports/async__client__packer_pecl__co50.xml \
	reports/async__client__packer_pecl__co100.xml
	@$(call make_report,Async, \
		"async__client__packer_pecl__co10": "co10"$(comma) \
		"async__client__packer_pecl__co25": "co25"$(comma) \
		"async__client__packer_pecl__co50": "co50"$(comma) \
		"async__client__packer_pecl__co100": "co100" \
	)

.PHONY: bench-async-connectors
bench-async-connectors: \
	reports/async__tarantool__co25.xml \
	reports/async__client__packer_pecl__co25.xml
	@$(call make_report,Async, \
		"async__tarantool__co25": "Tarantool"$(comma) \
		"async__client__packer_pecl__co25": "Client" \
	)

.PHONY: bench-async-client-protocols
bench-async-client-protocols: \
	reports/async__client__packer_pecl__protocol_bin__co25.xml \
	reports/async__client__packer_pecl__protocol_sql__co25.xml
	@$(call make_report,Async, \
		"async__client__packer_pecl__protocol_bin__co25": "Binary"$(comma) \
		"async__client__packer_pecl__protocol_sql__co25": "Sql" \
	)


# swoole

reports/swoole__tarantool__co%.xml: vendor
	@export TNT_BENCH_COROUTINES=$* && $(call run_bench,Swoole,Tarantool)

reports/swoole__client__packer_pecl__co%.xml: vendor
	@export TNT_BENCH_PACKER=pecl TNT_BENCH_COROUTINES=$* && $(call run_bench,Swoole,Client)

reports/swoole__client__packer_pecl__protocol_bin__co%.xml: vendor
	@export TNT_BENCH_PACKER=pecl TNT_BENCH_COROUTINES=$* && $(call run_bench,Swoole,Client,--filter=select --filter=insert --filter=update --filter=delete)

reports/swoole__client__packer_pecl__protocol_sql__co%.xml: vendor
	@export TNT_BENCH_PACKER=pecl TNT_BENCH_COROUTINES=$* && $(call run_bench,Swoole,ClientSql,--filter=select --filter=insert --filter=update --filter=delete)

.PHONY: bench-swoole-coroutines
bench-swoole-coroutines: \
	reports/swoole__client__packer_pecl__co10.xml \
	reports/swoole__client__packer_pecl__co25.xml \
	reports/swoole__client__packer_pecl__co50.xml \
	reports/swoole__client__packer_pecl__co100.xml
	@$(call make_report,Swoole, \
		"swoole__client__packer_pecl__co10": "co10"$(comma) \
		"swoole__client__packer_pecl__co25": "co25"$(comma) \
		"swoole__client__packer_pecl__co50": "co50"$(comma) \
		"swoole__client__packer_pecl__co100": "co100" \
	)

.PHONY: bench-swoole-connectors
bench-swoole-connectors: \
	reports/swoole__tarantool__co25.xml \
	reports/swoole__client__packer_pecl__co25.xml
	@$(call make_report,Swoole, \
		"swoole__tarantool__co25": "Tarantool"$(comma) \
		"swoole__client__packer_pecl__co25": "Client" \
	)

.PHONY: bench-swoole-client-protocols
bench-swoole-client-protocols: \
	reports/swoole__client__packer_pecl__protocol_bin__co25.xml \
	reports/swoole__client__packer_pecl__protocol_sql__co25.xml
	@$(call make_report,Swoole, \
		"swoole__client__packer_pecl__protocol_bin__co25": "Binary"$(comma) \
		"swoole__client__packer_pecl__protocol_sql__co25": "Sql" \
	)


# parallel

reports/parallel__tarantool__t%.xml: vendor
	@export TNT_BENCH_THREADS=$* && $(call run_bench,Parallel,Tarantool)

reports/parallel__client__packer_pecl__t%.xml: vendor
	@export TNT_BENCH_PACKER=pecl TNT_BENCH_THREADS=$* && $(call run_bench,Parallel,Client)

reports/parallel__client__packer_pecl__protocol_bin__t%.xml: vendor
	@export TNT_BENCH_PACKER=pecl TNT_BENCH_THREADS=$* && $(call run_bench,Parallel,Client,--filter=select --filter=insert --filter=update --filter=delete)

reports/parallel__client__packer_pecl__protocol_sql__t%.xml: vendor
	@export TNT_BENCH_PACKER=pecl TNT_BENCH_THREADS=$* && $(call run_bench,Parallel,ClientSql,--filter=select --filter=insert --filter=update --filter=delete)

.PHONY: bench-parallel-threads
bench-parallel-threads: \
	reports/parallel__client__packer_pecl__t2.xml \
	reports/parallel__client__packer_pecl__t4.xml \
	reports/parallel__client__packer_pecl__t8.xml \
	reports/parallel__client__packer_pecl__t16.xml \
	reports/parallel__client__packer_pecl__t32.xml
	@$(call make_report,Parallel, \
		"parallel__client__packer_pecl__t2": "t2"$(comma) \
		"parallel__client__packer_pecl__t4": "t4"$(comma) \
		"parallel__client__packer_pecl__t8": "t8"$(comma) \
		"parallel__client__packer_pecl__t16": "t16"$(comma) \
		"parallel__client__packer_pecl__t32": "t32" \
	)

.PHONY: bench-parallel-connectors
bench-parallel-connectors: \
	reports/parallel__tarantool__t16.xml \
	reports/parallel__client__packer_pecl__t16.xml
	@$(call make_report,Parallel, \
		"parallel__tarantool__t16":"Tarantool"$(comma) \
		"parallel__client__packer_pecl__t16":"Client" \
	)

.PHONY: bench-parallel-client-protocols
bench-parallel-client-protocols: \
	reports/parallel__client__packer_pecl__protocol_bin__t16.xml \
	reports/parallel__client__packer_pecl__protocol_sql__t16.xml
	@$(call make_report,Parallel, \
		"parallel__client__packer_pecl__protocol_bin__t16": "Binary"$(comma) \
		"parallel__client__packer_pecl__protocol_sql__t16": "Sql" \
	)


# extensions

reports/parallel_with_async__client__packer_pecl__t2__co10.xml: vendor
	@export TNT_BENCH_PACKER=pecl TNT_BENCH_THREADS=2 TNT_BENCH_COROUTINES=10 && $(call run_bench,ParallelWithAsync,Client)

reports/parallel_with_swoole__client__packer_pecl__t2__co10.xml: vendor
	@export TNT_BENCH_PACKER=pecl TNT_BENCH_THREADS=12 TNT_BENCH_COROUTINES=25 && $(call run_bench,ParallelWithSwoole,Client)


.PHONY: bench-extensions
bench-extensions: \
	reports/async__client__packer_pecl__co25.xml \
	reports/swoole__tarantool__co25.xml \
	reports/parallel__tarantool__t16.xml \
	reports/parallel__client__packer_pecl__t16.xml \
	reports/parallel_with_async__client__packer_pecl__t2__co10.xml
	@$(call make_report,Sync, \
		"async__client__packer_pecl__co25": "Client (async)"$(comma) \
		"swoole__tarantool__co25": "Tarantool (swoole)"$(comma) \
		"parallel__tarantool__t16": "Tarantool (parallel)"$(comma) \
		"parallel__client__packer_pecl__t16": "Client (parallel)"$(comma) \
		"parallel_with_async__client__packer_pecl__t2__co10": "Client (parallel+async)" \
	)
