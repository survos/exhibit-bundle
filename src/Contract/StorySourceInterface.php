<?php

declare(strict_types=1);

namespace Survos\ExhibitBundle\Contract;

/**
 * Somewhere story.v1 documents come from. StoryFinder asks every tagged source.
 *
 * The bundle ships FolioStorySource (published stories carried in folios); an application adds
 * its own, e.g. a user's ordered bookmark folder projected as a story. A source decides who may
 * see a story; StoryFinder only aggregates.
 */
interface StorySourceInterface
{
    public const string TAG = 'survos_exhibit.story_source';

    /** @return iterable<array<string, mixed>> stories this source lists publicly (GET /stories) */
    public function stories(): iterable;

    /** @return array<string, mixed>|null one story by id, including unlisted ones the viewer may see */
    public function story(string $id): ?array;
}
