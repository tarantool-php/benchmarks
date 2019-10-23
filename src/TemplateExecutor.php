<?php

declare(strict_types=1);

namespace Tarantool\Benchmarks;

use PhpBench\Benchmark\Metadata\SubjectMetadata;
use PhpBench\Benchmark\Remote\Launcher;
use PhpBench\Benchmark\Remote\Payload;
use PhpBench\Executor\BenchmarkExecutorInterface;
use PhpBench\Model\Iteration;
use PhpBench\Model\Result\MemoryResult;
use PhpBench\Model\Result\TimeResult;
use PhpBench\Registry\Config;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class TemplateExecutor implements BenchmarkExecutorInterface
{
    private const TEMPLATE_ENV_NAME = 'TNT_BENCH_TEMPLATE';
    private const TEMPLATE_DEFAULT = 'default.php.tpl';
    private const COROUTINES_ENV_NAME = 'TNT_BENCH_COROUTINES';
    private const COROUTINES_DEFAULT = 25;
    private const THREADS_ENV_NAME = 'TNT_BENCH_THREADS';
    private const THREADS_DEFAULT = 16;

    /**
     * @var Launcher
     */
    private $launcher;

    private $configPath;

    public function __construct(Launcher $launcher, string $configPath)
    {
        $this->launcher = $launcher;
        $this->configPath = $configPath;
    }

    public function configure(OptionsResolver $options) : void
    {
    }

    public function execute(SubjectMetadata $subjectMetadata, Iteration $iteration, Config $config) : void
    {
        $benchmarkClass = $subjectMetadata->getBenchmark()->getClass();
        $benchmark = new $benchmarkClass();
        [$init, $exec] = $benchmark->{$subjectMetadata->getName()}();
        $revolutions = $iteration->getVariant()->getRevolutions();
        $coroutines = $_SERVER[self::COROUTINES_ENV_NAME] ?? self::COROUTINES_DEFAULT;
        $threads = $_SERVER[self::THREADS_ENV_NAME] ?? self::THREADS_DEFAULT;
        $templateBaseName = $_SERVER[self::TEMPLATE_ENV_NAME] ?? self::TEMPLATE_DEFAULT;

        $tokens = [
            'class' => $subjectMetadata->getBenchmark()->getClass(),
            'subject' => $subjectMetadata->getName(),
            'revolutions' => $revolutions,
            'coroutines' => $coroutines,
            'threads' => $threads,
            'revsPerCoroutine' => round($revolutions / $coroutines),
            'revsPerThread' => round($revolutions / $threads),
            'revsPerThreadPerCoroutine' => round($revolutions / $threads / $coroutines),
            'beforeMethods' => var_export($subjectMetadata->getBeforeMethods(), true),
            'afterMethods' => var_export($subjectMetadata->getAfterMethods(), true),
            'parameters' => var_export($iteration->getVariant()->getParameterSet()->getArrayCopy(), true),
            'warmup' => $iteration->getVariant()->getWarmup() ?: 0,
            'init' => $init,
            'exec' => $exec,
        ];

        $templatePath = $this->resolveTemplatePath($templateBaseName);
        $payload = $this->launcher->payload($templatePath, $tokens);

        $this->launch($payload, $iteration);
    }

    private function launch(Payload $payload, Iteration $iteration) : void
    {
        $result = $payload->launch();

        if (isset($result['buffer']) && $result['buffer']) {
            throw new \RuntimeException(sprintf('Benchmark made some noise: %s', $result['buffer']));
        }

        $iteration->setResult(new TimeResult($result['time']));
        $iteration->setResult(MemoryResult::fromArray($result['mem']));
    }

    private function resolveTemplatePath(string $templateBaseName) : string
    {
        $paths = [
            $this->configPath,
            (new \ReflectionClass($this))->getFileName(),
        ];

        foreach ($paths as $path) {
            $templatePath = sprintf('%s/%s', dirname($path), $templateBaseName);
            if (file_exists($templatePath)) {
                return $templatePath;
            }
        }

        throw new \RuntimeException(sprintf('Failed to find template "%s".', $templateBaseName));
    }
}
