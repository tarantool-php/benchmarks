<?php

// disable garbage collection
gc_disable();

// repress any output from the user scripts
ob_start();

$class = '{{ class }}';
$beforeMethods = {{ beforeMethods }};
$afterMethods = {{ afterMethods }};
$bootstrap = '{{ bootstrap }}';
$parameters = {{ parameters }};
$warmup = {{ warmup }};

if ($bootstrap) {
    call_user_func(function () use ($bootstrap) {
        require_once($bootstrap);
    });
}

$benchmark = new $class();

foreach ($beforeMethods as $beforeMethod) {
    $benchmark->$beforeMethod($parameters);
}

// warmup if required
if ($warmup) {
    {{ init }}
    for ($i = 0; $i < $warmup; ++$i) {
        {{ exec }}
    }
}

Swoole\Runtime::enableCoroutine();

$time = benchmark();

Co\run(static function() use ($afterMethods, $benchmark) {
    foreach ($afterMethods as $afterMethod) {
        $benchmark->$afterMethod($parameters);
    }
});

$buffer = ob_get_clean();

echo serialize([
    'mem' => [
        // observer effect - getting memory usage affects memory usage. order
        // counts, peak is probably the best metric.
        'peak' => memory_get_peak_usage(),
        'final' => memory_get_usage(),
        'real' => memory_get_usage(true),
    ],
    'time' => (int) $time,
    'buffer' => $buffer,
]);

function benchmark()
{
    $task = static function () {
        Co\run(static function() {
            for ($i = 0; $i < {{ coroutines }}; ++$i) {
                go(static function () {
                    {{ init }}
                    for ($j = 0; $j < {{ revsPerWorkerPerCoroutine }}; ++$j) {
                        {{ exec }}
                    }
                });
            }
        });
    };

    $startTime = microtime(true);

    $workers = [];
    for ($i = 0; $i < {{ workers }}; ++$i) {
        $process = new \Swoole\Process($task);
        $pid = $process->start();
        $workers[$pid] = true;
    }

    while ($workers) {
        $ret = Swoole\Process::wait();
        unset($workers[$ret['pid']]);
    }

    $endTime = microtime(true);

    return ($endTime * 1000000) - ($startTime * 1000000);
}

exit(0);
