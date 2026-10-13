<?php

declare(strict_types=1);

namespace Survos\ExhibitBundle\Service;

use Survos\FolioBundle\Entity\Folio;
use Survos\FolioBundle\Entity\Row;
use Survos\FolioBundle\Service\FolioService;
use Survos\HarvestBundle\Catalog\HarvestCatalog;

/**
 * Which local folios carry a `story` core, and their story rows.
 *
 * Stories are ordinary folio rows (dto_type "story", core "story"): Harvest's story-normalize
 * writes norm/story.jsonl and folio:build ingests it like any other core, so a reader app that
 * holds the folio already holds its stories -- no dataset registry, no separate artifact.
 *
 * Which folios have one comes from harvest-bundle's catalog (each artifact's published
 * coreCounts), never from opening folio files: a hub holds thousands of folios and a handful
 * have stories. Only those few are opened, and only to read their story rows.
 */
final class StoryIndex
{
    public const string CORE = 'story';

    /** Pipeline bookkeeping folio ingest adds to dto_data; not part of a story.v1 document. */
    private const array PIPELINE_FIELDS = ['stage', 'source', 'pageCount'];

    /** @var list<string>|null */
    private ?array $codes = null;

    public function __construct(
        private readonly FolioService $folios,
        private readonly HarvestCatalog $catalog,
    ) {
    }

    /** @return list<string> folio codes (provider/dataset) that have a story core */
    public function folioCodes(): array
    {
        return $this->codes ??= array_values(array_filter(
            $this->catalog->folioCodesWithCore(self::CORE),
            $this->folios->exists(...),
        ));
    }

    /** @return list<array<string, mixed>> the story.v1 documents in one folio, in the site's own order */
    public function stories(string $folioCode): array
    {
        $rows = $this->folios->context($folioCode)->em->createQueryBuilder()
            ->select('r')
            ->from(Row::class, 'r')
            ->where('r.core = :core')
            ->setParameter('core', $folioCode . ':' . self::CORE)
            ->getQuery()
            ->getResult();

        $stories = [];
        foreach ($rows as $row) {
            $story = array_diff_key(($row->dtoData ?? []) + ($row->extras ?? []), array_flip(self::PIPELINE_FIELDS));
            if (isset($story['id'])) {
                $stories[] = $story;
            }
        }
        usort($stories, static fn (array $a, array $b): int => [$a['ordinal'] ?? PHP_INT_MAX, $a['id']] <=> [$b['ordinal'] ?? PHP_INT_MAX, $b['id']]);

        return $stories;
    }

    public function folio(string $folioCode): ?Folio
    {
        return $this->folios->context($folioCode)->em->find(Folio::class, $folioCode);
    }
}
