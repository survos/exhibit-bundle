<?php

declare(strict_types=1);

namespace Survos\ExhibitBundle\Service;

use Survos\ExhibitBundle\Dto\StoryDto;

/**
 * Finds published story.v1-shaped documents across every local folio with a `story` core
 * (see StoryIndex: Harvest's provider:curatescape:story-normalize writes them, folio:build
 * ingests them as ordinary rows) -- the canonical artifact, never a projection; see
 * tacman/story-contract's own design rule.
 *
 * No index beyond StoryIndex's which-folios cache: real story counts are small (a handful of
 * tours per dataset today), so reading every story-bearing folio per call is the right amount
 * of engineering -- see survos-sites/sos#6's "no fancy relations, these are small" framing,
 * which applies here just as much as it does to rut's own client-side Dexie cache.
 */
final class StoryFinder
{
    public function __construct(
        private readonly StoryIndex $index,
    ) {
    }

    /** @return iterable<StoryDto> every story, from each folio's story core */
    public function findAll(): iterable
    {
        foreach ($this->index->folioCodes() as $folioCode) {
            yield from $this->findForDataset($folioCode);
        }
    }

    /** @return iterable<StoryDto> stories belonging to one folio (one "site") */
    public function findForDataset(string $datasetKey): iterable
    {
        if (!\in_array($datasetKey, $this->index->folioCodes(), true)) {
            return;
        }
        foreach ($this->index->stories($datasetKey) as $story) {
            yield StoryDto::fromArray($story);
        }
    }

    public function find(string $id): ?StoryDto
    {
        foreach ($this->findAll() as $story) {
            if ($story->getId() === $id) {
                return $story;
            }
        }

        return null;
    }

    /** Every story, across every dataset, that has a stop referencing $ref -- the object-page backlink lookup. */
    public function findStopsForRef(string $ref): array
    {
        $stops = [];
        foreach ($this->findAll() as $story) {
            foreach ($story->getBookmarkStops() as $stop) {
                if ($stop->ref === $ref) {
                    $stops[] = $stop;
                }
            }
        }

        return $stops;
    }
}
