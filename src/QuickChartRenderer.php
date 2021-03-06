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
            'labels' => [],
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

        $labels = array_keys($labels);
        $datasets = self::normalizeDatasets($datasets, $config, $labels);

        $imageFilename = self::resolveImageFilename($config);
        $url = $this->saveChartImage($imageFilename, [
            'labels' => $labels,
            'datasets' => $datasets,
        ]);

        $this->output->writeln("Url: <comment>$url</comment>");
        $this->output->writeln("Chart image saved to <comment>$imageFilename</comment>");
    }

    private function buildDataset(Element $tableEl, Config $config, array &$datasets, array &$labels, array &$colors) : void
    {
        $title = $tableEl->getAttribute('title');
        if (!preg_match('/benchmark: (?<benchmark>.+)Bench, tag: (?<tag>[^\s,]*)/', $title, $matches)) {
            throw new \LogicException(sprintf('%s requires "benchmark" and "tag" to be defined in the "break" option.', __CLASS__));
        }

        foreach ($tableEl->query('.//row') as $rowEl) {
            if (!isset($datasets[$title])) {
                if ($matches['tag']) {
                    $datasetLabel = $config['labels'][$matches['tag']] ?? str_replace('_', ' ', $matches['tag']);
                } else {
                    $datasetLabel = $matches['benchmark'];
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
                $datasets[$title]['data'][$label] = preg_replace('/[^\d.]/', '', $value);
            }
        }
    }

    private static function normalizeDatasets(array $datasets, Config $config, array $labels) : array
    {
        if ([] !== $config['labels']) {
            $datasets = self::addMissingDatasetLabels($config['labels'], $datasets);
        }

        foreach ($datasets as $key => $dataset) {
            $fullData = $dataset['data'] + array_fill_keys($labels, 0);
            $normData = [];
            foreach ($labels as $label) {
                $normData[] = $fullData[$label];
            }
            $datasets[$key]['data'] = $normData;
        }

        return array_values($datasets);
    }

    private static function addMissingDatasetLabels(array $datasetLabels, array $datasets) : array
    {
        foreach ($datasetLabels as $tag => $datasetLabel) {
            foreach ($datasets as $dataset) {
                if ($dataset['label'] === $datasetLabel) {
                    unset($datasetLabels[$tag]);
                }
            }
        }

        foreach ($datasetLabels as $datasetLabel) {
            $datasets[] = [
                'label' => "$datasetLabel (n/a)",
                'data' => [],
                'borderWidth' => 0,
            ];
        }

        return $datasets;
    }

    private function saveChartImage(string $filename, array $chartData) : string
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

        return $url;
    }

    private static function resolveImageFilename(Config $config) : string
    {
        $dir = preg_replace('/{{\s*?root_dir\s*?}}/', dirname(__DIR__), $config['dir']);
        $basename = $config['basename'] ?? 'chart.'.time();

        return "$dir/$basename.png";
    }
}
