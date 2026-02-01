<?php

namespace Dherlou\ContaoKKMSitePackage\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class DateTimeExtension extends AbstractExtension
{
    // data

    private const O_CLOCK = ' Uhr';

    private const WEEKDAYS = [
        1 => 'Montag',
        2 => 'Dienstag',
        3 => 'Mittwoch',
        4 => 'Donnerstag',
        5 => 'Freitag',
        6 => 'Samstag',
        7 => 'Sonntag'
    ];

    // filter registration

    public function getFilters(): array
    {
        return [
            new TwigFilter('kkm_datetime', [$this, 'transformToDateTime']),
            new TwigFilter('kkm_datetime_recurring', [$this, 'transformToDateTimeRecurring']),
            new TwigFilter('kkm_oclock', [$this, 'transformTimeToOClock']),
            new TwigFilter('kkm_weekday', [$this, 'transformToWeekday']),
        ];
    }

    // filter implementation
    
    public function transformToDateTime(string $date, ?string $time = null, string|int|null $weekday = null, bool $lineBreak = false): string
    {
        $dateString = '';
        
        if ($weekday) {
            $dateString .= (is_numeric($weekday) ? $this->transformToWeekday($weekday) : $weekday) . ', ';
        }
        
        if (strpos($date, '–') !== false) {
            $dateString .= implode(' – ', array_map(function($d) use ($lineBreak) {
                $d = str_replace(' ', ',' . ($lineBreak ? "\n" : ' '), $d);
                if (strpos($d, ':') !== false) {
                    $d = $this->transformTimeToOClock($d);
                }
                return $d;
            }, explode('–', $date)));
        } else {
            $dateString .= $date;

            if ($time) {
                $dateString .= ', ' . $this->transformTimeToOClock($time);
            }
        }
        
        return $dateString;
    }

    public function transformToDateTimeRecurring(string $recurring): string
    {
        $pattern = '/(?<=<time datetime="[^"]{25}">)(.*)(?=<\/time>)/';

        preg_match($pattern, $recurring, $matches);
        $formattedDateTime = $this->transformToDateTime($matches[1]);

        return preg_replace(
            $pattern,
            $formattedDateTime,
            $recurring
        );
    }

    public function transformTimeToOClock(string $time): string
    {
        return $time . self::O_CLOCK;
    }
    
    public function transformToWeekday(int $weekday): string
    {
        return self::WEEKDAYS[$weekday] ?? '';
    }
}
