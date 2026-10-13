<?php

declare(strict_types=1);

namespace Survos\ExhibitBundle\Service;

/**
 * Finds "site" metadata -- the branding/skin a picker shows before fetching any tour bodies
 * (logo, contact, license), plus the story ids that belong to it. One site per story-bearing
 * folio; its code is the folio's dataset part (curatescape/scioto -> scioto), matching the slug
 * md's CuratescapeProvider::storyNormalize() writes into norm/site.json.
 *
 * norm/site.json itself is not carried in the folio yet. Until Harvest publishes it as the
 * folio's `site` property, a site is the folio's own label/description plus its story ids;
 * once that property exists, its fields (logo, contact, url, license...) are merged on top.
 *
 * "Site" is deliberately not called "provider": that word already means something else in this
 * ecosystem (dc/smith/euro-style data providers) -- a site here is closer to "the thing you'd
 * install as a separate app" (Scioto Historical's own site, eventually a TourForge source),
 * scoped to one folio.
 */
final class SiteFinder
{
    public const string PROPERTY = 'site';

    public function __construct(
        private readonly StoryIndex $index,
    ) {
    }

    /** @return iterable<array<string, mixed>> */
    public function findAll(): iterable
    {
        foreach ($this->index->folioCodes() as $folioCode) {
            yield $this->site($folioCode);
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

    /** @return array<string, mixed> */
    private function site(string $folioCode): array
    {
        $folio = $this->index->folio($folioCode);
        $published = $folio?->get(self::PROPERTY);

        return array_filter([
            'code' => substr($folioCode, (int) strrpos($folioCode, '/') + 1),
            'label' => $folio?->label,
            'description' => $folio?->description,
            ...(\is_array($published) ? $published : []),
            'storyIds' => array_column($this->index->stories($folioCode), 'id'),
            // Not part of site.json itself -- SiteController::stories() scopes StoryFinder with it.
            'datasetKey' => $folioCode,
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
