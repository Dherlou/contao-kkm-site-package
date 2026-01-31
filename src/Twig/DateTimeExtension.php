<?php

namespace Dherlou\ContaoKKMSitePackage\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class DateTimeExtension extends AbstractExtension
{
    // filter registration

    public function getFilters(): array
    {
        return [
            new TwigFilter('kkm_oclock', [$this, 'transformTimeToOClock']),
        ];
    }

    // filter implementation

    public function transformTimeToOClock(string $time): string
    {
        return $time . ' Uhr';
    }
}
