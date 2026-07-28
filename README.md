# Survos Exhibit Bundle

Symfony integration for authoring, resolving, previewing, and publishing Story
Contract documents.

The portable story definition lives in `tacman/story-contract`. This bundle is
the PHP/Symfony layer around that contract:

- persistence for stories, blocks, bookmarks, saved searches, and projections
- authoring UI for OpenFoto/zm-backed source material
- YAML import/export for editorial review
- reference resolution and snapshot publishing
- Twig components for server-rendered story pages
- API endpoints that export normalized story JSON
- adapters for target projections such as Omeka S and TimelineJS

The bundle must not make Symfony entities the canonical interchange format. The
canonical portable document is the Story Contract JSON document.

## Status

Most of the above is still aspirational. Built so far: `GET /stories` and
`GET /stories/{id}` (`StoryController`/`StoryFinder`), publishing whatever
`norm/story.jsonl` documents already exist across every known dataset
(currently: `md`'s Curatescape pipeline) — read-only, no persistence,
authoring, or projection adapters yet. This is the first real consumer:
`survos-sites/sos`'s `rut` app fetching the available tour list instead of a
hardcoded PHP source array (see survos-sites/sos#6).

## Responsibilities

This bundle owns:

- Doctrine entities and repositories
- workflow states such as draft, review, published, archived
- security voters and ownership checks
- admin forms and UX authoring screens
- OpenFoto/zm bookmark and search integrations
- resolver services for `openfoto:*`, `zm:*`, and `omeka:*` references
- Twig rendering and hydration hooks
- export commands and controllers

This bundle does not own:

- the framework-neutral JSON schema
- npm/browser rendering packages
- Omeka S internal data models
- TimelineJS slide authoring as the source format

## Target Contract

Stories exported by this bundle should validate against:

```text
https://github.com/tacman/story-contract
```

The bundle may store normalized relational data internally, but export should
produce:

```json
{
  "schema": "https://tacman.github.io/story-contract/schema/story.v1.schema.json",
  "version": 1,
  "id": "story:example",
  "title": "Example story",
  "bookmarks": [],
  "queries": [],
  "resources": {},
  "blocks": [],
  "connections": [],
  "projections": {}
}
```

## Documentation

- `docs/architecture.md` - boundaries between contract, bundle, renderers, and adapters.
- `docs/authoring-openfoto-zm.md` - authoring model for bookmarks and saved searches.
- `docs/yaml-workflow.md` - YAML import/export for non-programmer story editing.
- `docs/reference-resolution.md` - reference expansion and snapshot publishing.
- `docs/projections.md` - Symfony services for Story, Omeka S, TimelineJS, and JSON-LD projections.

