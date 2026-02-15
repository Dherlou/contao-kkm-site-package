<?php

namespace Dherlou\ContaoKKMSitePackage\Twig;

use DateTime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;


class DateTimeExtension extends AbstractExtension
{
    // data

    private const FORMAT_IN_DATE = 'd.m.Y';
    private const FORMAT_IN_DATE_TIME = self::FORMAT_IN_DATE . self::SEPARATOR_DATE_TIME_IN . self::FORMAT_IN_TIME;
    private const FORMAT_IN_TIME = 'H:i';
    private const FORMAT_OUT_DATE = 'l, d.m.Y';
    private const FORMAT_OUT_TIME = 'H:i';
    private const FORMAT_OUT_TIME_SUFFIX = ' Uhr';
    
    private const SEPARATOR_RANGE_SHORT = '–';
    private const SEPARATOR_RANGE_LONG = ' ' . self::SEPARATOR_RANGE_SHORT . ' ';
    private const SEPARATOR_RANGE_LINEBREAK = self::SEPARATOR_RANGE_LONG . "\n";
    private const SEPARATOR_DATE_TIME_IN = ' ';
    private const SEPARATOR_DATE_TIME_OUT = ', ';
    private const SEPARATOR_TIME_HOUR_MINUTE = ':';

    // filter registration

    public function getFilters(): array
    {
        return [
            new TwigFilter('kkm_datetime', [$this, 'parseAndFormatDateTimeStrings']),
            new TwigFilter('kkm_datetime_recurring', [$this, 'parseAndFormatDateTimeStringsRecurring']),
            new TwigFilter('kkm_time', [$this, 'parseAndFormatTimeStrings']),
        ];
    }

    // filter implementations

    public function parseAndFormatDateTimeStrings(?string $dateString = null, ?string $timeString = null, bool $lineBreak = true): string
    {
        list($start, $end) = $this->parseDateTimeStrings($dateString, $timeString);
        
        return $this->formatDateTimes($start, $end, $lineBreak);
    }

    public function parseAndFormatDateTimeStringsRecurring(string $recurring): string
    {
        $pattern = '/(?<=<time datetime="[^"]{25}">)(.*)(?=<\/time>)/';

        $match = preg_match($pattern, $recurring, $matches);

        if (!$match) {
            return $recurring;
        }

        $formattedDateTime = $this->parseAndFormatDateTimeStrings($matches[1]);

        return preg_replace(
            $pattern,
            $formattedDateTime,
            $recurring
        );
    }

    public function parseAndFormatTimeStrings(string $timeString, bool $startOnly = false): string
    {
        list($start, $end) = $this->parseDateTimeStrings('', $timeString);

        return $this->formatTimes($start, $startOnly ? null : $end);
    }

    // filter helpers

    private function parseDateTimeStrings(?string $dateString = null, ?string $timeString = null): array
    {
        list($dateStrings, $timeStrings) = $this->extractDateTimeStrings($dateString, $timeString);
        $dateTimes = [];

        for ($i = 0; $i < 2; $i++) {
            $dateStringI = $dateStrings[$i] ?? $dateStrings[0];
            $timeStringI = $timeStrings[$i] ?? $timeStrings[0] ?? null;

            $dateTimeString = implode(
                self::SEPARATOR_DATE_TIME_IN,
                [
                    $dateStringI ?? '',
                    $timeStringI ?? '',
                ]
            );
            $dateTimeStringTrimmed = trim($dateTimeString);

            $dateTimes[] = $this->parseDateTimeString($dateTimeStringTrimmed);
        }

        return $dateTimes;
    }

    private function extractDateTimeStrings(?string $dateString = null, ?string $timeString = null): array
    {
        $dateTimeStrings = explode(self::SEPARATOR_RANGE_SHORT, $dateString ?? '');

        $dateStrings = [];
        $timeStrings = [];

        if (str_contains($dateTimeStrings[0], self::SEPARATOR_DATE_TIME_IN)) {
            foreach ($dateTimeStrings as $dateTimeString) {
                $dateTimeStringParts = explode(self::SEPARATOR_DATE_TIME_IN, $dateTimeString);

                $timeStrings[] = array_slice($dateTimeStringParts, -1, 1)[0] ?? null;
                $dateStrings[] = array_slice($dateTimeStringParts, 0, count($dateTimeStringParts) - 1)[0] ?? null;                
            }
        } else {
            $dateStrings = $dateTimeStrings;
            $timeStrings = str_contains($timeString, self::SEPARATOR_TIME_HOUR_MINUTE) ?
                explode(self::SEPARATOR_RANGE_SHORT, $timeString) :
                [];
        }

        return [$dateStrings, $timeStrings];
    }

    private function parseDateTimeString(string $dateTimeString): ?DateTime
    {
        $dateTime = DateTime::createFromFormat(self::FORMAT_IN_DATE_TIME, $dateTimeString);

        $date = DateTime::createFromFormat(self::FORMAT_IN_DATE, $dateTimeString);
        if ($date !== false) {
            $date->setTime(0, 0);
        }

        $time = DateTime::createFromFormat(self::FORMAT_IN_TIME, $dateTimeString);

        return $dateTime ?: $date ?: $time ?: null;
    }

    private function formatDateTimes(?DateTime $start, ?DateTime $end, bool $lineBreak): string
    {
        $dateTimes = [];

        if ($start !== null) {
            $dateTimes[] = $this->formatDateTime($start);
        }

        if ($end !== null) {
            if ($this->areSameDate($start, $end) && !$this->areSameTime($start, $end)) {
                $dateTimes[0] = str_replace(
                    self::FORMAT_OUT_TIME_SUFFIX,
                    self::SEPARATOR_RANGE_SHORT . $this->formatTime($end),
                    $dateTimes[0]
                );
            } else if ($start != $end) {
                $dateTimes[] = $this->formatDateTime($end);
            }
        }

        $dateTimeStrings = implode(
            $lineBreak ? self::SEPARATOR_RANGE_LINEBREAK : self::SEPARATOR_RANGE_LONG,
            $dateTimes
        );
        $dateTimeStringsLinebreak = nl2br($dateTimeStrings);

        return $lineBreak ? $dateTimeStringsLinebreak : $dateTimeStrings;
    }

    private function formatTimes(?DateTime $start, ?DateTime $end): string
    {
        $times = [];

        if ($start !== null) {
            $times[] = $this->formatTime($start);
        }

        if ($end !== null) {
            $times[] = $this->formatTime($end);
        }

        return implode(self::SEPARATOR_RANGE_SHORT, $times);
    }

    private function formatDateTime(DateTime $dateTime): string
    {
        $output = $dateTime->format(self::FORMAT_OUT_DATE);

        if ($this->hasTimeset($dateTime)) {
            $output .= self::SEPARATOR_DATE_TIME_OUT . $this->formatTime($dateTime);
        }
        
        return $output;
    }

    private function formatTime(DateTime $dateTime): string
    {
        return $dateTime->format(self::FORMAT_OUT_TIME) . self::FORMAT_OUT_TIME_SUFFIX;
    }

    private function hasTimeSet(DateTime $dateTime): bool
    {
        return $dateTime->format(self::FORMAT_IN_TIME) != '00:00';
    }

    private function areSameDate(?DateTime $dateTime1, ?DateTime $dateTime2): bool
    {
        if ($dateTime1 === null || $dateTime2 === null) {
            return false;
        }

        return $dateTime1->format(self::FORMAT_IN_DATE) == $dateTime2->format(self::FORMAT_IN_DATE);
    }

    private function areSameTime(?DateTime $dateTime1, ?DateTime $dateTime2): bool
    {
        if ($dateTime1 === null || $dateTime2 === null) {
            return false;
        }

        return $dateTime1->format(self::FORMAT_IN_TIME) == $dateTime2->format(self::FORMAT_IN_TIME);
    }
}
