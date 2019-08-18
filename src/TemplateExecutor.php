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
    private const TEMPLATE_NAME = 'microtime.php.tpl';
    private const COROUTINES_ENV_NAME = 'TNT_BENCH_ASYNC_COROUTINES';
    private const COROUTINES_DEFAULT = 25;

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

        $tokens = [
            'class' => $subjectMetadata->getBenchmark()->getClass(),
            'subject' => $subjectMetadata->getName(),
            'revolutions' => $revolutions,
            'coroutines' => $coroutines,
            'revsPerCoroutine' => $revsPerCoroutine = $revolutions / $coroutines,
            'beforeMethods' => var_export($subjectMetadata->getBeforeMethods(), true),
            'afterMethods' => var_export($subjectMetadata->getAfterMethods(), true),
            'parameters' => var_export($iteration->getVariant()->getParameterSet()->getArrayCopy(), true),
            'warmup' => $iteration->getVariant()->getWarmup() ?: 0,
            'init' => $init,
            'exec' => $exec,
        ];

        $templatePath = $this->resolveTemplatePath();
        $payload = $this->launcher->payload($templatePath, $tokens);

        $this->launch($payload, $iteration);
    }

    private function launch(Payload $payload, Iteration $iteration) : void
    {
        $result = $payload->launch();

        if (isset($result['buffer']) && $result['buffer']) {
            throw new \RuntimeException(sprintf(
                'Benchmark made some noise: %s',
                $result['buffer']
            ));
        }

        $iteration->setResult(new TimeResult($result['time']));
        $iteration->setResult(MemoryResult::fromArray($result['mem']));
    }

    private function resolveTemplatePath() : string
    {
        $paths = [
            $this->configPath,
            (new \ReflectionClass($this))->getFileName(),
        ];

        foreach ($paths as $path) {
            $templatePath = sprintf('%s/%s', dirname($path), self::TEMPLATE_NAME);
            if (file_exists($templatePath)) {
                return $templatePath;
            }
        }

        throw new \RuntimeException(sprintf('Failed to find template "%s".', self::TEMPLATE_NAME));
    }
}
