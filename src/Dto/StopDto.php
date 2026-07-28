<?php

declare(strict_types=1);

namespace Survos\ExhibitBundle\Dto;

/**
 * A story-contract "stop" — one block, as it appears within a specific story, plus enough of the
 * bookmark it's paired with to render a stop card without a second lookup.
 *
 * Not a new core, not a new folio entity — this is a typed read over data that already exists in
 * story.jsonl's `blocks[]` (the ordered narrative unit: title, body, media, geo/ordinal in
 * `meta`) and, when present, the matching `bookmarks[]` entry (the lighter reference used for
 * the backlink case: "does object X appear as a stop anywhere?"). A Stop is deliberately NOT
 * owned by one story the way a Page is owned by one document — the same object can be a stop in
 * any number of stories, each with its own label/note/ordinal, which is exactly the shape
 * bookmarks already have and pages don't.
 */
final class StopDto implements \JsonSerializable
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $storyId,
        public readonly ?string $ref,
        public readonly ?string $title,
        public readonly ?string $body,
        public readonly ?string $subtitle,
        public readonly ?float $lat,
        public readonly ?float $lng,
        public readonly ?int $ordinal,
    ) {
    }

    /** @param array<string,mixed> $block one entry from story.jsonl's blocks[] */
    public static function fromBlock(array $block, ?string $storyId = null): self
    {
        $meta = $block['meta'] ?? [];
        $ref = $block['media'][0]['ref'] ?? null;

        return new self(
            id: (string) ($block['id'] ?? ''),
            storyId: $storyId,
            ref: is_string($ref) ? $ref : null,
            title: isset($block['title']) ? (string) $block['title'] : null,
            body: isset($block['body']) ? (string) $block['body'] : null,
            subtitle: isset($meta['subtitle']) ? (string) $meta['subtitle'] : null,
            lat: isset($meta['lat']) ? (float) $meta['lat'] : null,
            lng: isset($meta['lng']) ? (float) $meta['lng'] : null,
            ordinal: isset($meta['ordinal']) ? (int) $meta['ordinal'] : null,
        );
    }

    /**
     * @param array<string,mixed> $bookmark one entry from story.jsonl's bookmarks[]
     * @param array<string,mixed> $resources the story's resources{}, keyed by ref
     */
    public static function fromBookmark(array $bookmark, ?string $storyId, array $resources = []): self
    {
        $ref = isset($bookmark['ref']) ? (string) $bookmark['ref'] : null;
        $resource = $ref !== null ? ($resources[$ref] ?? null) : null;

        return new self(
            id: (string) ($bookmark['id'] ?? ''),
            storyId: $storyId,
            ref: $ref,
            title: isset($bookmark['label']) ? (string) $bookmark['label'] : ($resource['title'] ?? null),
            body: null,
            subtitle: isset($bookmark['note']) ? (string) $bookmark['note'] : null,
            lat: isset($resource['meta']['lat']) ? (float) $resource['meta']['lat'] : null,
            lng: isset($resource['meta']['lng']) ? (float) $resource['meta']['lng'] : null,
            ordinal: null,
        );
    }

    /** @return array<string,mixed> */
    public function jsonSerialize(): array
    {
        return array_filter([
            'id' => $this->id,
            'storyId' => $this->storyId,
            'ref' => $this->ref,
            'title' => $this->title,
            'body' => $this->body,
            'subtitle' => $this->subtitle,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'ordinal' => $this->ordinal,
        ], static fn(mixed $v): bool => $v !== null);
    }
}
