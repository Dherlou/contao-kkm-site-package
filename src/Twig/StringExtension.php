<?php

namespace Dherlou\ContaoKKMSitePackage\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class StringExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('kkm_html_to_plain_text', [$this, 'htmlToPlainText']),
        ];
    }
    
    public function htmlToPlainText(?string $text): string
    {
        // Replace tags that imply whitespace with a space
        $text = preg_replace(
            '/<(br|hr)\s*\/?>|<\/(p|div|li|tr|h[1-6])>/i',
            ' ',
            $text ?? ''
        );

        // remove tags
        $text = strip_tags($text);

        // decode html entities (&nbsp; etc.)
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // replace multiple spaces with single space
        $text = preg_replace('/\s+/', ' ', $text);
        
        // trim whitespace
        $text = trim($text);

        return $text;
    }
}