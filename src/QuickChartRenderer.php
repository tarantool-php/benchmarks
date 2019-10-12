<?php

declare(strict_types=1);

namespace Tarantool\Benchmarks;

use PhpBench\Console\OutputAwareInterface;
use PhpBench\Dom\Document;
use PhpBench\Dom\Element;
use PhpBench\Formatter\Formatter;
use PhpBench\Registry\Config;
use PhpBench\Report\RendererInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class QuickChartRenderer implements RendererInterface, OutputAwareInterface
{
    private const BAR_COLORS = [
        'rgb(66,133,244)',
        'rgb(219,68,55)',
        'rgb(244,180,0)',
        'rgb(15,157,88)',
        'rgb(255,109,0)',
        'rgb(70,189,198)',
        'rgb(171,48,196)',
    ];

    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct(Formatter $formatter)
    {
        $this->formatter = $formatter;
    }

    public function setOutput(OutputInterface $output) : void
    {
        $this->output = $output;
    }

    public function configure(OptionsResolver $options) : void
    {
        $options->setDefaults([
            'basename' => null,
            'dir' => '{{ root_dir }}/reports/charts',
            'label_column' => 'subject',
            'data_column' => 'mean',
        ]);
    }

    public function render(Document $reportDom, Config $config) : void
    {
        $datasets = [];
        $labels = [];
        $colors = self::BAR_COLORS;

        foreach ($reportDom->firstChild->query('./report') as $reportEl) {
            foreach ($reportEl->query('.//table') as $tableEl) {
                $this->buildDataset($tableEl, $config, $datasets, $labels, $colors);
            }
        }

        $data = [
            'labels' => array_keys($labels),
            'datasets' => array_values($datasets),
        ];

        $this->saveChartImage(self::resolveImageName($config), $data);
    }

    private function buildDataset(Element $tableEl, Config $config, array &$datasets, array &$labels, array &$colors) : void
    {
        $title = $tableEl->getAttribute('title');
        if (!preg_match('/benchmark: (?<benchmark>.+)Bench, tag: (?<tag>[^\s,]*)/', $title, $matches)) {
            throw new \LogicException(sprintf('%s requires "benchmark" and "tag" to be defined in the "break" option.', __CLASS__));
        }

        foreach ($tableEl->query('.//row') as $rowEl) {
            if (!isset($datasets[$title])) {
                $datasetLabel = $matches['benchmark'];
                if ($matches['tag']) {
                    $datasetLabel = sprintf('%s (%s)', $datasetLabel, str_replace('_', ' ', $matches['tag']));
                }

                $datasets[$title] = [
                    'label' => $datasetLabel,
                    'data' => [],
                    'borderWidth' => 0,
                ];

                if ($colors) {
                    $datasets[$title]['backgroundColor'] = array_shift($colors);
                }
            }

            $label = $rowEl->queryOne(sprintf("./cell[@name='%s']", $config['label_column']))->nodeValue;
            $labels[$label] = true;

            $formatterParams = [];
            foreach ($rowEl->query('./formatter-param') as $paramEl) {
                $formatterParams[$paramEl->getAttribute('name')] = $paramEl->nodeValue;
            }

            $dataEl = $rowEl->queryOne(sprintf("./cell[@name='%s']", $config['data_column']));
            $value = $dataEl->nodeValue;

            if ($dataEl->hasAttribute('class')) {
                $classes = explode(' ', $dataEl->getAttribute('class'));
                $value = $this->formatter->applyClasses($classes, $value, $formatterParams);
                $datasets[$title]['data'][] = preg_replace('/[^\d.]/', '', $value);
            }
        }
    }

    private function saveChartImage(string $filename, array $chartData) : void
    {
        $url = 'https://quickchart.io/chart?'.http_build_query([
            'width' => 300,
            'height' => 200,
            'c' => json_encode([
                'type' => 'bar',
                'data' => $chartData,
                'options' => [
                    'legend' => [
                        'labels' => [
                            'fontSize' => 5,
                            'boxWidth' => 5,
                        ],
                    ],
                    'scales' => [
                        'xAxes' => [[
                            'ticks' => [
                                'fontSize' => 5,
                                'padding' => -6,
                            ],
                            'gridLines' => [
                                'display' => false,
                                'drawBorder' => false,
                            ],
                            'barPercentage' => 0.8,
                            'categoryPercentage' => 0.5,
                        ]],
                        'yAxes' => [[
                            'ticks' => [
                                'fontSize' => 5,
                                'padding' => 5,
                                'beginAtZero' => true,
                            ],
                            'gridLines' => [
                                'lineWidth' => 0.5,
                            ],
                            'scaleLabel' => [
                                'display' => true,
                                'labelString' => 'ops/s',
                                'padding' => 2,
                                'fontSize' => 7,
                            ],
                        ]],
                    ],
                ],
            ]),
        ]);

        (new Filesystem())->dumpFile($filename, file_get_contents($url));

        $this->output->writeln("Url: <comment>$url</comment>");
        $this->output->writeln("File saved to <comment>$filename</comment>");
    }

    private static function resolveImageName(Config $config) : string
    {
        $dir = preg_replace('/{{\s*?root_dir\s*?}}/', dirname(__DIR__), $config['dir']);
        $basename = $config['basename'] ?? 'chart.'.time();

        return "$dir/$basename.png";
    }
}
