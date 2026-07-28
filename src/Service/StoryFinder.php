<?php

declare(strict_types=1);

namespace Survos\ExhibitBundle\Service;

use Survos\FolioBundle\Service\FolioRegistry;
use Survos\JsonlBundle\IO\JsonlReader;

/**
 * Finds published story.v1-shaped documents across every dataset that has one.
 * Currently that means datasets with a norm/story.jsonl (see md's
 * provider:curatescape:story-normalize -- the canonical artifact, never a
 * projection; see tacman/story-contract's own design rule).
 *
 * No caching, no index: real story counts are small (a handful of tours per
 * dataset today), so a full scan on every call is the right amount of
 * engineering -- see survos-sites/sos#6's "no fancy relations, these are
 * small" framing, which applies here just as much as it does to rut's own
 * client-side Dexie cache.
 */
final class StoryFinder
{
    public function __construct(
        private readonly FolioRegistry $folioRegistry,
    ) {
    }

    /** @return iterable<array<string, mixed>> raw story.v1 documents */
    public function findAll(): iterable
    {
        foreach ($this->folioRegistry->datasets(all: true) as $dataset) {
            yield from $this->readStoryFile($dataset);
        }
    }

    /** @return iterable<array<string, mixed>> raw story.v1 documents belonging to one dataset (one "site") */
    public function findForDataset(string $datasetKey): iterable
    {
        foreach ($this->folioRegistry->datasets($datasetKey) as $dataset) {
            yield from $this->readStoryFile($dataset);
        }
    }

    /** @return array<string, mixed>|null */
    public function find(string $id): ?array
    {
        foreach ($this->findAll() as $story) {
            if ($story['id'] === $id) {
                return $story;
            }
        }

        return null;
    }

    /** @return iterable<array<string, mixed>> */
    private function readStoryFile(\Survos\DatasetBundle\Entity\DatasetInfo $dataset): iterable
    {
        // sourceFile()'s $core param is really just "which <core>.jsonl under norm/" -- 'story'
        // works the same way 'obj' does for row content, even though a story isn't a row core.
        // There's no _folio-enrichment stage for stories (nothing AI-claims-enriches a story
        // document), so the "enriched" half of sourceFile() always misses and it falls through
        // to the plain norm/story.jsonl check, which is exactly the lookup this needs.
        $storyFile = $this->folioRegistry->sourceFile($dataset, 'story');
        if ($storyFile === null) {
            return;
        }

        foreach (JsonlReader::open($storyFile) as $story) {
            if (\is_array($story) && isset($story['id'])) {
                yield $story;
            }
        }
    }
}
