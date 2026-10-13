<?php

declare(strict_types=1);

namespace Survos\ExhibitBundle\Service;

use Survos\ExhibitBundle\Contract\StorySourceInterface;

/** Published stories carried in folios' `story` cores (see StoryIndex). */
final class FolioStorySource implements StorySourceInterface
{
    public function __construct(
        private readonly StoryIndex $index,
    ) {
    }

    public function stories(): iterable
    {
        foreach ($this->index->folioCodes() as $folioCode) {
            yield from $this->index->stories($folioCode);
        }
    }

    public function story(string $id): ?array
    {
        foreach ($this->stories() as $story) {
            if ($story['id'] === $id) {
                return $story;
            }
        }

        return null;
    }
}
