<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Image;

class ChartExportService
{
    protected array $defaultOptions = [
        'width' => 800,
        'height' => 400,
        'background' => '#ffffff',
        'format' => 'png',
        'fontSize' => 12,
        'fontColor' => '#4a5568',
        'gridColor' => '#e2e8f0',
        'lineColor' => '#4299e1',
        'barColor' => '#4299e1',
        'margin' => [
            'top' => 40,
            'right' => 40,
            'bottom' => 40,
            'left' => 60
        ]
    ];

    public function generateChart($type, $data, array $options = []): string
    {
        $options = array_merge($this->defaultOptions, $options);

        $img = Image::canvas(
            $options['width'],
            $options['height'],
            $options['background']
        );

        switch($type) {
            case 'line':
                $this->drawLineChart($img, $data, $options);
                break;
            case 'bar':
                $this->drawBarChart($img, $data, $options);
                break;
            case 'pie':
                $this->drawPieChart($img, $data, $options);
                break;
            case 'comparison':
                $this->drawComparisonChart($img, $data, $options);
                break;
            default:
                throw new \InvalidArgumentException('نوع نمودار نامعتبر است.');
        }

        $filename = sprintf(
            'charts/%s_%s.%s',
            Str::slug($type),
            time(),
            $options['format']
        );

        Storage::makeDirectory('charts');
        $path = storage_path('app/public/' . $filename);
        $img->save($path);

        return $filename;
    }

    protected function drawLineChart($img, $data, $options): void
    {
        $points = $this->calculateDataPoints($data, $options);

        $this->drawGrid($img, $options);

        for ($i = 1; $i < count($points); $i++) {
            $img->line(
                $points[$i-1]['x'],
                $points[$i-1]['y'],
                $points[$i]['x'],
                $points[$i]['y'],
                function ($draw) use ($options) {
                    $draw->color($options['lineColor']);
                    $draw->width(2);
                }
            );
        }

        foreach ($points as $point) {
            $img->circle(4, $point['x'], $point['y'], function ($draw) use ($options) {
                $draw->background($options['lineColor']);
            });
        }

        $this->drawLabels($img, $data, $options);
        $this->drawLegend($img, $data, $options);
    }

    protected function drawBarChart($img, $data, $options): void
    {
        $this->drawGrid($img, $options);

        $barWidth = ($options['width'] - $options['margin']['left'] - $options['margin']['right']) / count($data);
        $maxValue = max(array_column($data, 'value'));
        $scale = ($options['height'] - $options['margin']['top'] - $options['margin']['bottom']) / $maxValue;

        foreach ($data as $index => $item) {
            $height = $item['value'] * $scale;
            $x = $options['margin']['left'] + ($index * $barWidth);
            $y = $options['height'] - $options['margin']['bottom'] - $height;

            $img->rectangle(
                $x + 5,
                $y,
                $x + $barWidth - 5,
                $options['height'] - $options['margin']['bottom'],
                function ($draw) use ($options) {
                    $draw->background($options['barColor']);
                }
            );

            $img->text(
                number_format($item['value']),
                $x + ($barWidth / 2),
                $y - 15,
                function ($font) use ($options) {
                    $font->color($options['fontColor']);
                    $font->size($options['fontSize']);
                    $font->align('center');
                }
            );
        }

        $this->drawLabels($img, $data, $options);
    }

    protected function drawPieChart($img, $data, $options): void
    {
        $total = array_sum(array_column($data, 'value'));
        $centerX = $options['width'] / 2;
        $centerY = $options['height'] / 2;
        $radius = min($centerX, $centerY) - max($options['margin']);

        $startAngle = 0;
        $colors = $this->generateColors(count($data));

        foreach ($data as $index => $item) {
            $slice = ($item['value'] / $total) * 360;

            $img->circle($radius, function ($draw) use ($centerX, $centerY, $startAngle, $slice, $colors, $index) {
                $draw->background($colors[$index]);
                $draw->pie($centerX, $centerY, $startAngle, $startAngle + $slice);
            });

            $labelAngle = deg2rad($startAngle + ($slice / 2));
            $labelRadius = $radius * 0.8;
            $labelX = $centerX + cos($labelAngle) * $labelRadius;
            $labelY = $centerY + sin($labelAngle) * $labelRadius;

            $img->text(
                $item['label'] . ' (' . round(($item['value'] / $total) * 100) . '%)',
                $labelX,
                $labelY,
                function ($font) use ($options) {
                    $font->color($options['fontColor']);
                    $font->size($options['fontSize']);
                    $font->align('center');
                    $font->valign('middle');
                }
            );

            $startAngle += $slice;
        }
    }

    protected function drawComparisonChart($img, $data, $options): void
    {
        $this->drawGrid($img, $options);

        $barWidth = ($options['width'] - $options['margin']['left'] - $options['margin']['right']) / (count($data) * 2);
        $maxValue = max(array_column(array_merge($data['current'], $data['previous']), 'value'));
        $scale = ($options['height'] - $options['margin']['top'] - $options['margin']['bottom']) / $maxValue;

        foreach ($data['current'] as $index => $currentItem) {
            $previousItem = $data['previous'][$index] ?? null;
            $x = $options['margin']['left'] + ($index * $barWidth * 2);

            $currentHeight = $currentItem['value'] * $scale;
            $img->rectangle(
                $x,
                $options['height'] - $options['margin']['bottom'] - $currentHeight,
                $x + $barWidth - 2,
                $options['height'] - $options['margin']['bottom'],
                function ($draw) {
                    $draw->background('#4299e1');
                }
            );

            if ($previousItem) {
                $previousHeight = $previousItem['value'] * $scale;
                $img->rectangle(
                    $x + $barWidth,
                    $options['height'] - $options['margin']['bottom'] - $previousHeight,
                    $x + ($barWidth * 2) - 2,
                    $options['height'] - $options['margin']['bottom'],
                    function ($draw) {
                        $draw->background('#9f7aea');
                    }
                );
            }
        }

        $this->drawComparisonLegend($img, $options);
    }

    protected function calculateDataPoints($data, $options): array
    {
        $points = [];
        $maxValue = max(array_column($data, 'value'));
        $xStep = ($options['width'] - $options['margin']['left'] - $options['margin']['right']) / (count($data) - 1);
        $yScale = ($options['height'] - $options['margin']['top'] - $options['margin']['bottom']) / $maxValue;

        foreach ($data as $index => $item) {
            $points[] = [
                'x' => $options['margin']['left'] + ($index * $xStep),
                'y' => $options['height'] - $options['margin']['bottom'] - ($item['value'] * $yScale)
            ];
        }

        return $points;
    }

    protected function drawGrid($img, $options): void
    {
        $stepY = ($options['height'] - $options['margin']['top'] - $options['margin']['bottom']) / 5;

        for ($i = 0; $i <= 5; $i++) {
            $y = $options['margin']['top'] + ($i * $stepY);

            $img->line(
                $options['margin']['left'],
                $y,
                $options['width'] - $options['margin']['right'],
                $y,
                function ($draw) use ($options) {
                    $draw->color($options['gridColor']);
                }
            );
        }
    }

    protected function drawLabels($img, $data, $options): void
    {
        $xStep = ($options['width'] - $options['margin']['left'] - $options['margin']['right']) / (count($data) - 1);

        foreach ($data as $index => $item) {
            $x = $options['margin']['left'] + ($index * $xStep);

            $img->text(
                $item['label'],
                $x,
                $options['height'] - ($options['margin']['bottom'] / 2),
                function ($font) use ($options) {
                    $font->color($options['fontColor']);
                    $font->size($options['fontSize']);
                    $font->align('center');
                }
            );
        }
    }

    protected function drawLegend($img, $data, $options): void
    {
        $legendX = $options['width'] - $options['margin']['right'] + 20;
        $legendY = $options['margin']['top'];

        foreach ($data as $index => $series) {
            if (isset($series['name'])) {
                $img->rectangle(
                    $legendX,
                    $legendY,
                    $legendX + 20,
                    $legendY + 10,
                    function ($draw) use ($series, $options) {
                        $draw->background($series['color'] ?? $options['lineColor']);
                    }
                );

                $img->text(
                    $series['name'],
                    $legendX + 25,
                    $legendY + 5,
                    function ($font) use ($options) {
                        $font->color($options['fontColor']);
                        $font->size($options['fontSize']);
                        $font->align('left');
                        $font->valign('middle');
                    }
                );

                $legendY += 20;
            }
        }
    }

    protected function generateColors($count): array
    {
        $colors = [];
        for ($i = 0; $i < $count; $i++) {
            $hue = ($i * 360) / $count;
            $colors[] = $this->hslToRgb($hue, 0.7, 0.5);
        }
        return $colors;
    }

    protected function hslToRgb($h, $s, $l): string
    {
        $h /= 360;
        $r = $g = $b = $l;

        if ($s != 0) {
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;

            $r = $this->hue2rgb($p, $q, $h + 1/3);
            $g = $this->hue2rgb($p, $q, $h);
            $b = $this->hue2rgb($p, $q, $h - 1/3);
        }

        return sprintf(
            '#%02x%02x%02x',
            round($r * 255),
            round($g * 255),
            round($b * 255)
        );
    }

    private function hue2rgb($p, $q, $t)
    {
        if ($t < 0) $t += 1;
        if ($t > 1) $t -= 1;
        if ($t < 1/6) return $p + ($q - $p) * 6 * $t;
        if ($t < 1/2) return $q;
        if ($t < 2/3) return $p + ($q - $p) * (2/3 - $t) * 6;
        return $p;
    }

    private function drawComparisonLegend($img, $options): void
    {
    }
}
