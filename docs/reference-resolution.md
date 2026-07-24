# Reference Resolution

Stories should be authored with references and published with enough expanded
data to render reliably.

## Reference Format

Recommended provider-prefixed references:

```text
openfoto:item:FI0021005
zm:artifact:123
omeka:item:456
wikidata:Q60
```

References are stable logical ids. A resolver maps them to API calls, Doctrine
entities, or cached records.

## Resolver Contract

A resolver should accept a ref and return a normalized resource:

```php
interface StoryReferenceResolverInterface
{
    public function supports(string $ref): bool;

    /**
     * @return array<string, mixed>
     */
    public function resolve(string $ref, ResolutionContext $context): array;
}
```

Normalized resource output should follow Story Contract resource fields:

```json
{
  "id": "openfoto:item:FI0021005",
  "type": "image",
  "title": "The Broadie Building",
  "url": "https://example.test/media/FI0021005.jpg",
  "thumb": "https://example.test/media/FI0021005-thumb.jpg",
  "source": "https://fortepan.us/categories/all/FI0021005",
  "rights": "CC-BY-SA 4.0"
}
```

## Expansion Policies

### References

Store refs only. Rendering requires live access to the source system.

### Expanded

Include resource data needed by the renderer. Useful for previews and API output.

### Snapshot

Include refs, expanded resources, query criteria, captured query results, and
publish metadata. Recommended for publication.

## Caching

The bundle should cache resolved references for preview responsiveness, but the
cache must not become canonical story content. A published snapshot can embed
expanded data as part of the exported document.

