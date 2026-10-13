<?php

declare(strict_types=1);

namespace Survos\ExhibitBundle\Service;

use Survos\ExhibitBundle\Contract\StorySourceInterface;
use Survos\ExhibitBundle\Dto\StoryDto;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Finds story.v1 documents across every StorySourceInterface: the bundle's FolioStorySource plus
 * whatever the application contributes (zm: bookmark folders).
 *
 * Folio stories: published story.v1-shaped documents across every local folio with a `story` core
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
    /** @param iterable<StorySourceInterface> $sources */
    public function __construct(
        private readonly StoryIndex $index,
        #[AutowireIterator(StorySourceInterface::TAG)] private readonly iterable $sources,
    ) {
    }

    /** @return iterable<StoryDto> every listed story, from every source */
    public function findAll(): iterable
    {
        foreach ($this->sources as $source) {
            foreach ($source->stories() as $story) {
                yield StoryDto::fromArray($story);
            }
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

    /** One story by id from whichever source has it, including unlisted ones the viewer may see. */
    public function find(string $id): ?StoryDto
    {
        foreach ($this->sources as $source) {
            if (($story = $source->story($id)) !== null) {
                return StoryDto::fromArray($story);
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
