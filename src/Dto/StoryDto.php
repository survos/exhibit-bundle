<?php

declare(strict_types=1);

namespace Survos\ExhibitBundle\Dto;

/**
 * Typed read over one story.v1.schema.json document (see tacman/story-contract). Wraps the raw
 * array rather than re-modeling every field, so `jsonSerialize()` stays byte-compatible with what
 * StoryController's /stories and /stories/{id} endpoints already publish (rut's Dexie sync
 * depends on that exact shape) -- this gives PHP call sites (the stop backlink lookup, a future
 * TourDto-shaped map view) typed accessors without touching the wire format.
 *
 * "Story" is the canonical unit -- see CuratescapeProvider::storyNormalize() -- not a projection.
 * A "Tour" (Curatescape's own term) IS a Story here, 1:1; there's no separate TourDto today
 * because nothing yet needs fields a Story doesn't have. If a map/sequence view ever needs
 * tour-specific framing beyond getStops(), that's the point to add one, not before.
 */
final class StoryDto implements \JsonSerializable
{
    /** @param array<string,mixed> $raw the full, unmodified story.v1 document */
    private function __construct(
        private readonly array $raw,
    ) {
    }

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public function getId(): string
    {
        return (string) ($this->raw['id'] ?? '');
    }

    public function getTitle(): string
    {
        return (string) ($this->raw['title'] ?? '');
    }

    public function getDek(): ?string
    {
        return isset($this->raw['dek']) ? (string) $this->raw['dek'] : null;
    }

    public function getStatus(): ?string
    {
        return isset($this->raw['status']) ? (string) $this->raw['status'] : null;
    }

    /** @return list<string> credit labels */
    public function getCredits(): array
    {
        $credits = $this->raw['credits'] ?? [];

        return array_values(array_filter(array_map(
            static fn (mixed $c): ?string => is_array($c) ? ($c['label'] ?? null) : (is_string($c) ? $c : null),
            is_array($credits) ? $credits : [],
        )));
    }

    public function getCoverImageUrl(): ?string
    {
        $url = $this->raw['meta']['coverImageUrl'] ?? null;

        return is_string($url) ? $url : null;
    }

    /** @return list<StopDto> every block, in story order -- the ordered "tour" reading of this story */
    public function getStops(): array
    {
        $blocks = $this->raw['blocks'] ?? [];
        if (!is_array($blocks)) {
            return [];
        }

        return array_values(array_map(
            fn (array $block): StopDto => StopDto::fromBlock($block, $this->getId()),
            $blocks,
        ));
    }

    /** @return list<StopDto> every bookmark, in listed order -- the lighter "index" reading, no body text */
    public function getBookmarkStops(): array
    {
        $bookmarks = $this->raw['bookmarks'] ?? [];
        $resources = $this->raw['resources'] ?? [];
        if (!is_array($bookmarks)) {
            return [];
        }

        return array_values(array_map(
            fn (array $bookmark): StopDto => StopDto::fromBookmark($bookmark, $this->getId(), is_array($resources) ? $resources : []),
            $bookmarks,
        ));
    }

    /** True when $ref matches any bookmark in this story -- the backlink check: "is this object a stop here?" */
    public function hasStopForRef(string $ref): bool
    {
        foreach ($this->getBookmarkStops() as $stop) {
            if ($stop->ref === $ref) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string,mixed> the full, unmodified story.v1 document */
    public function jsonSerialize(): array
    {
        return $this->raw;
    }
}
