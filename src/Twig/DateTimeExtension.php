<?php

namespace Dherlou\ContaoKKMSitePackage\Twig;

use DateTimeImmutable;
use DateTimeZone;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class DateTimeExtension extends AbstractExtension
{
    // data

    private const O_CLOCK = ' Uhr';
    
    private const SEPARATOR_RANGE_SHORT = '–';
    private const SEPARATOR_RANGE_LONG = ' ' . self::SEPARATOR_RANGE_SHORT . ' ';
    private const SEPARATOR_DATE_TIME_SPACE = ' ';
    private const SEPARATOR_DATE_TIME_INLINE = ', ';
    private const SEPARATOR_DATE_TIME_LINEBREAK = ",\n";
    private const SEPARATOR_TIME_HOUR_MINUTE = ':';

    private const TZ = 'Europe/Berlin';

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
            new TwigFilter('kkm_time_cal', [$this, 'transformToTimeCalendar']),
            new TwigFilter('kkm_weekday', [$this, 'transformToWeekday']),
            new TwigFilter('kkm_weekday_from_tstamp', [$this, 'transformToWeekdayFromTStamp']),
        ];
    }

    // filter implementation
    
    public function transformToDateTime(string $date, ?string $time = null, string|int|null $weekday = null, bool $lineBreak = false): string
    {
        $dateString = '';
        
        if ($weekday) {
            $dateString .= (is_numeric($weekday) ? $this->transformToWeekday($weekday) : $weekday) . self::SEPARATOR_DATE_TIME_INLINE;
        }
        
        if (strpos($date, self::SEPARATOR_RANGE_SHORT) !== false) {
            $dateString .= implode(self::SEPARATOR_RANGE_LONG, array_map(function($d) use ($lineBreak) {
                $d = str_replace(
                    self::SEPARATOR_DATE_TIME_SPACE,
                    $lineBreak ? self::SEPARATOR_DATE_TIME_LINEBREAK : self::SEPARATOR_DATE_TIME_INLINE,
                    $d
                );
                if (strpos($d, self::SEPARATOR_TIME_HOUR_MINUTE) !== false) {
                    $d = $this->transformTimeToOClock($d);
                }
                return $d;
            }, explode(self::SEPARATOR_RANGE_SHORT, $date)));
        } else {
            $dateString .= $date;

            if ($time) {
                $dateString .= self::SEPARATOR_DATE_TIME_INLINE . $this->transformTimeToOClock($time);
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

    public function transformToTimeCalendar(string $time): string
    {
        if (empty($time)) {
            return '';
        }

        $startTime = explode(self::SEPARATOR_RANGE_SHORT, $time)[0];
        return $this->transformTimeToOClock($startTime);
    }
    
    public function transformToWeekday(?int $weekday): ?string
    {
        return self::WEEKDAYS[$weekday] ?? null;
    }

    public function transformToWeekdayFromTStamp(int $tStamp): ?string
    {
        $dateTime = DateTimeImmutable::createFromFormat('U', $tStamp, new DateTimeZone(self::TZ));

        return $this->transformToWeekday($dateTime?->format('N'));
    }
}
