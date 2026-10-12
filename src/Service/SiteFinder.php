<?php

declare(strict_types=1);

namespace Survos\ExhibitBundle\Service;

use Survos\DataContracts\Path\DataPaths;
use Survos\FolioBundle\Service\FolioRegistry;

/**
 * Finds "site" metadata -- the branding/skin a picker shows before fetching any tour bodies
 * (logo, contact, license), plus the story ids that belong to it. See md's
 * CuratescapeProvider::storyNormalize(), which writes norm/site.json as a sibling of
 * norm/story.jsonl -- same dataset, same stage, not a new entity/table.
 *
 * "Site" is deliberately not called "provider": that word already means something else in this
 * ecosystem (dc/smith/euro-style data providers, dataset-bundle's own Provider entity) -- a site
 * here is closer to "the thing you'd install as a separate app" (Scioto Historical's own site,
 * eventually a TourForge source), scoped to one dataset.
 */
final class SiteFinder
{
    public function __construct(
        private readonly FolioRegistry $folioRegistry,
        private readonly DataPaths $dataPaths,
    ) {
    }

    /** @return iterable<array<string, mixed>> */
    public function findAll(): iterable
    {
        foreach ($this->folioRegistry->datasets(all: true) as $dataset) {
            $file = $this->dataPaths->stageDir($dataset->datasetKey, 'normalize') . '/site.json';
            if (!\is_file($file)) {
                continue;
            }

            $site = \json_decode((string) \file_get_contents($file), true, flags: \JSON_THROW_ON_ERROR);
            if (\is_array($site) && isset($site['code'])) {
                // Not part of site.json itself (that file is written without knowledge of its own
                // folio-registry key) -- needed by SiteController::stories() to scope StoryFinder
                // to just this site's dataset.
                $site['datasetKey'] = $dataset->datasetKey;
                yield $site;
            }
        }
    }

    /** @return array<string, mixed>|null */
    public function find(string $code): ?array
    {
        foreach ($this->findAll() as $site) {
            if ($site['code'] === $code) {
                return $site;
            }
        }

        return null;
    }
}
