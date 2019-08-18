<?php

declare(strict_types=1);

namespace Tarantool\Benchmarks;

use PhpBench\DependencyInjection\Container;
use PhpBench\DependencyInjection\ExtensionInterface;
use PhpBench\Executor\CompositeExecutor;
use PhpBench\Extension\CoreExtension;

final class ExecutorExtension implements ExtensionInterface
{
    public function load(Container $container) : void
    {
        $container->register('benchmark.executor.template', static function (Container $container) {
            return new CompositeExecutor(
                $container->get('executor.benchmark.template'),
                $container->get(CoreExtension::SERVICE_EXECUTOR_METHOD_REMOTE)
            );
        }, ['benchmark_executor' => ['name' => 'template']]);

        $container->register('executor.benchmark.template', static function (Container $container) {
            return new TemplateExecutor(
                $container->get(CoreExtension::SERVICE_REMOTE_LAUNCHER),
                $container->getParameter('config_path')
            );
        });

        $container->register('report_renderer.quickchart', static function (Container $container) {
            return new QuickChartRenderer($container->get('phpbench.formatter'));
        }, ['report_renderer' => ['name' => 'quickchart']]);
    }

    public function getDefaultConfig() : array
    {
        return [];
    }
}
