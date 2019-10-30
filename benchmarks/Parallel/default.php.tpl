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
    for ($i = 0; $i < $warmup; ++$i) {
        {{ init }}
        {{ exec }}
    }
}

$time = benchmark();

foreach ($afterMethods as $afterMethod) {
    $benchmark->$afterMethod($parameters);
}

$buffer = ob_get_contents();
ob_end_clean();

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
        require_once('{{ bootstrap }}');

        {{ init }};
        for ($j = 0; $j < {{ revsPerWorker }}; ++$j) {
            {{ exec }}
        }
    };

    $startTime = microtime(true);

    $futures = [];
    for ($i = 0; $i < {{ workers }}; ++$i) {
        $futures[] = (new \parallel\Runtime())->run($task);
    }

    while ($futures) {
        foreach ($futures as $i => $future) {
            if ($future->done()) {
                unset($futures[$i]);
            }
        }
    }

    $endTime = microtime(true);

    return ($endTime * 1000000) - ($startTime * 1000000);
}

exit(0);
