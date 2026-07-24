# Architecture

`survos/exhibit-bundle` is the Symfony authoring and publishing layer for Story
Contract documents.

## Package Boundary

```text
tacman/story-contract
  Portable JSON schema, examples, and documentation.

survos/exhibit-bundle
  Symfony authoring, persistence, reference resolution, Twig rendering, and export.

@survos/story-renderer
  Optional browser-side rendering and hydration.

projection adapters
  Derived outputs such as Omeka S API payloads, TimelineJS JSON, JSON-LD, or static HTML.
```

The contract must remain usable without Symfony. The bundle may depend on the
contract, but the contract must not depend on the bundle.

## Runtime Flow

1. Editor creates a story in Symfony.
2. Editor adds bookmarks or saved searches from OpenFoto/zm.
3. Editor arranges blocks and adds narrative text.
4. Bundle stores draft state using Doctrine.
5. Resolver expands references for preview or publishing.
6. Exporter emits normalized Story Contract JSON.
7. Projection services render or export target formats.

## Suggested Services

```text
survos_exhibit.story_exporter
survos_exhibit.reference_resolver
survos_exhibit.snapshot_publisher
survos_exhibit.projection.story_renderer
survos_exhibit.projection.omeka_s
survos_exhibit.projection.timelinejs
survos_exhibit.projection.json_ld
```

## Suggested Entities

Entity names are implementation details, but a practical first model is:

- `Story`
- `StoryRevision`
- `StoryBlock`
- `StoryBookmark`
- `StoryQuery`
- `StoryConnection`
- `StoryProjection`

Entity data should round-trip to the Story Contract. If a field cannot export to
the contract, it is probably application metadata and belongs in `meta`.

## Twig Target

The server-rendered component should be useful without JavaScript:

```twig
<twig:ExhibitStory :story="storyDocument" />
```

Hydration should be progressive:

```html
<article data-exhibit-story data-story-url="/api/stories/broadie-building">
  ...
</article>
```

JavaScript enhances navigation, galleries, comparison tools, and optional
projection widgets. It should not own the canonical story state.

