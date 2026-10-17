<?php

declare(strict_types=1);

namespace Survos\ExhibitBundle\Service;

use Survos\DataContracts\Vocabulary\Core;
use Survos\FolioBundle\Entity\Folio;
use Survos\FolioBundle\Entity\Row;
use Survos\FolioBundle\Service\FolioService;
use Survos\HarvestBundle\Catalog\HarvestCatalog;

/**
 * Which local folios carry a `tour` core, and the story.v1 documents on its rows.
 *
 * Stories are the story.v1 view of ordinary folio tour rows (core "tour", Core::TOUR): Harvest
 * writes norm/tour.jsonl with the story.v1 document on each row and folio:build ingests it like any
 * other core, so a reader app that holds the folio already holds its stories -- no dataset registry, no separate artifact.
 *
 * Which folios have one comes from harvest-bundle's catalog (each artifact's published
 * coreCounts), never from opening folio files: a hub holds thousands of folios and a handful
 * have stories. Only those few are opened, and only to read their story rows.
 */
final class StoryIndex
{
    public const string CORE = Core::TOUR;

    /** Pipeline bookkeeping folio ingest adds to dto_data; not part of a story.v1 document. */
    private const array PIPELINE_FIELDS = ['stage', 'source', 'pageCount'];

    /** @var list<string>|null */
    private ?array $codes = null;

    public function __construct(
        private readonly FolioService $folios,
        private readonly HarvestCatalog $catalog,
    ) {
    }

    /** @return list<string> folio codes (provider/dataset) that have a tour core */
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
            ->setParameter('core', $folioCode.':'.self::CORE)
            ->getQuery()
            ->getResult();

        $stories = [];
        foreach ($rows as $row) {
            $story = array_diff_key(($row->dtoData ?? []) + ($row->extras ?? []), array_flip(self::PIPELINE_FIELDS));
            // A tour row carries a story.v1 document only when its producer wrote one (Curatescape,
            // Chijal); a tour that is only links and a stops[] summary (AIC) has no blocks to serve.
            if (isset($story['id'], $story['blocks'])) {
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
