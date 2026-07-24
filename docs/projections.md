# Projection Services

Projection services convert canonical Story Contract documents into target
outputs.

## Story HTML Projection

Default Symfony projection:

- accepts normalized story JSON
- renders Twig blocks
- supports no-JavaScript fallback
- adds `data-*` hooks for hydration

Service:

```text
survos_exhibit.projection.story_renderer
```

## Omeka S Projection

Omeka S projection maps story blocks and queries to site pages and page blocks.

Story projection input:

```json
{
  "omeka_s": {
    "site": {
      "slug": "broadie-building",
      "title": "Three Businesses, One Building"
    },
    "pages": [
      {
        "slug": "overview",
        "title": "Overview",
        "blocks": [
          { "type": "html", "from": "block:intro" },
          { "type": "media", "from": "block:broadies" },
          { "type": "browsePreview", "query": "query:waverly-storefronts" }
        ]
      }
    ]
  }
}
```

The adapter should generate Omeka S API payloads or module block data from this.
Do not store Omeka's internal page-block payload as the canonical story.

## TimelineJS Projection

TimelineJS projection exists for interoperability, not for authoring.

The adapter converts `when` fields into TimelineJS `start_date`, `end_date`, and
`display_date` fields.

## JSON-LD Projection

JSON-LD projection emits semantic graph output from:

- story metadata
- resources
- connections
- provenance
- credits

The authoring contract may include `@context`, `@id`, and `@type`, but should not
require editors to work directly in verbose JSON-LD.

