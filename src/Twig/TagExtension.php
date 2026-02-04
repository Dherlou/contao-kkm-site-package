<?php

namespace Dherlou\ContaoKKMSitePackage\Twig;

use Contao\PageModel;
use numero2\TagsBundle\TagsModel;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TagExtension extends AbstractExtension
{

    /* data */

    private const TYPE_PRIORITY_LOOKUP = [
        /* Main servie types */
        'Ökumenischer Gottesdienst' => 0,
        'Hl. Messe' => 1,
        'Wortgottesfeier' => 2,
        /* Sacraments */
        'Taufe' => 10,
        'Beichte' => 11,
        'Erstkommunion' => 12,
        'Firmung' => 13,
        'Trauung' => 14,
        'Priesterweihe' => 15,
        'Krankensalbung' => 16,
        /* Age groups */
        'Kinderkirche' => 20,
        'Seniorengottesdienst' => 21,
        /* Others */
        'Beisetzung' => 30,
        'Trauerfeier' => 31,
        'Eucharistische Anbetung' => 32,
        'Chorandacht' => 33,
    ];

    private const LOCATION_PRIORITY_LOOKUP = [
        'Meiningen' => 0,
        'Suhl' => 1,
        'Wolfmannshausen' => 2,
        'Schmalkalden' => 3,
        'Schleusingen' => 4,
        'Zella-Mehlis' => 5,
    ];


    /* filters */

    public function getFilters(): array
    {
        return [
            new TwigFilter('kkm_tags_oos_list', [$this, 'transformTagsForOOSList']),
        ];
    }

    public function transformTagsForOOSList(array $tags): array
    {
        $sorted = $this->getSortedTagsByPriorities($tags, self::TYPE_PRIORITY_LOOKUP, self::LOCATION_PRIORITY_LOOKUP);

        return $this->getLimitedTags($sorted, 2, 1);
    }

    /* functions */

    public function getFunctions(): array
    {
        return [
            new TwigFunction('kkm_tags_load_serialized', [$this, 'loadSerializedTags']),
            new TwigFunction('kkm_tags_load_list_page', [$this, 'loadListPage']),
        ];
    }

    public function loadSerializedTags(?string $serializedTagIds): array
    {
        if (!$serializedTagIds) {
            return [];
        }

        $tagIds = unserialize($serializedTagIds);

        $tags = array_map(
            fn($id) => TagsModel::findById($id)?->tag,
            $tagIds
        );

        return array_filter($tags);
    }

    public function loadListPage(PageModel $page): ?PageModel
    {
        return PageModel::findById($page->pid);
    }

    /* helper functions */

    private function getSortedTagsByPriorities(array $tags, array ...$priorityLookups): array
    {
        usort($tags, function ($tag1, $tag2) use ($priorityLookups) {
            return $this->getSortOrder($tag1, $tag2, ...$priorityLookups);
        });

        return $tags;
    }

    private function getSortOrder(string $tag1, string $tag2, array ...$priorityLookups): int
    {
        foreach ($priorityLookups as $lookup) {
            $tag1InGroup = isset($lookup[$tag1]);
            $tag2InGroup = isset($lookup[$tag2]);

            if ($tag1InGroup || $tag2InGroup) {
                if ($tag1InGroup && $tag2InGroup) {
                    return $lookup[$tag1] <=> $lookup[$tag2];
                }

                // tag1 wins if it is in the higher-priority group
                return $tag1InGroup ? -1 : 1;
            }
        }

        // fallback if neither tag is in any priority group
        return strcasecmp($tag1, $tag2);
    }

    private function getLimitedTags(array $tags, ?int $firstN = null, ?int $lastN = null): array
    {
        $first = $firstN ?? count($tags);
        $last = $lastN ?? 0;

        if ($first < 0 || $last < 0 || $first + $last > count($tags)) {
            return $tags;
        }

        return array_unique(
            array_merge(
                array_slice($tags, 0, $first),
                array_slice($tags, -$last)
            )
        );
    }
}
