# OpenFoto and zm Authoring

OpenFoto and zm should provide source material for stories through bookmarks and
saved searches. Editors should not be forced to create final render output while
collecting material.

## Bookmark Authoring

A bookmark records that an editor selected a resource for possible use.

**This is not an entity to invent — it already exists.**
`Survos\FolioBundle\Bookmark\Entity\BookmarkBase` (`survos/folio-bundle`) is a
real, live `MappedSuperclass`, and both OpenFoto's and zm's concrete
`App\Entity\Bookmark` already extend it (see the bundle's own
`docs/bookmarks.md`). It stores a folio row's full identity as plain scalar
columns — `provider`, `dataset`, `coreCode`, `localId` — plus a `label`
display cache, and is managed through `BookmarkManager`
(`isBookmarked()`/`add()`/`remove()`/`toggle()`/`move()`). This bundle should
build on that, not duplicate it.

Fields `BookmarkBase` already has:

- `id` (Ulid)
- `created`
- `provider` / `dataset` / `coreCode` / `localId` (full row identity)
- `dtoType`, `label` (display cache, snapshotted at bookmark time)

Fields the contract's `bookmark` shape needs that `BookmarkBase` does not yet
have: `note`, `tags`, `pinned`. These are generically useful beyond story
authoring (a personal note/tag/pin on any bookmark, not just a story-authoring
one), so the right home is likely `BookmarkBase` itself rather than a
story-specific wrapper entity — but that's a `folio-bundle` schema change
shared by every host app (OpenFoto, zm, and any future consumer), not
something to add unilaterally here. Flagged for a `folio-bundle` PR, not yet
done.

**`ref` construction — a real gap, not yet resolved.** `story-contract`'s own
docs (`docs/story-format.md`'s Identifiers section) show refs like
`openfoto:item:FI0021005` — a bare `{provider}:{type}:{id}` triple. That's
lossy for a folio-backed row: `BookmarkBase` needs all four of
`provider`/`dataset`/`coreCode`/`localId` to resolve back to a real `Row`
(`Survos\FolioBundle\Entity\Row::id()`/`getRp()`), and a provider can span
multiple datasets, so `provider:type:id` alone is ambiguous/unresolvable in
the general case. Until `story-contract` and its examples are reconciled on
this, this bundle's exporter should build the fully-qualified form —
`{provider}:{dataset}:{coreCode}:{localId}` — not the shorter triple, so no
information is lost round-tripping through a story document.

Contract export (`note`/`tags`/`pinned` shown as the target shape, pending the
`BookmarkBase` fields above; `{provider}:{dataset}:{coreCode}:{localId}` are
placeholders for whatever `BookmarkBase`'s real columns hold for this row —
not asserting a specific literal value here):

```json
{
  "id": "bookmark:FI0021005",
  "ref": "{provider}:{dataset}:{coreCode}:{localId}",
  "label": "The Broadie Building",
  "note": "Good opening image.",
  "tags": ["waverly", "storefront"],
  "pinned": true
}
```

## Saved Search Authoring

A saved search lets a story include a resource set without copying every result
into a block.

Bundle-side entity fields:

- provider
- resource type
- criteria JSON
- sort
- limit
- mode
- captured result refs for snapshots

Contract export:

```json
{
  "id": "query:waverly-storefronts",
  "provider": "openfoto",
  "resource": "items",
  "label": "Waverly storefront photos",
  "criteria": {
    "tags": ["waverly", "storefront"],
    "when": { "start": "1900", "end": "1930" }
  },
  "sort": ["when.start", "title"],
  "limit": 12,
  "mode": "snapshot",
  "results": [
    { "ref": "openfoto:item:FI0021005" }
  ]
}
```

## Story Board

Editors build an ordered sequence of blocks from bookmarks, searches, and text.

Block types should start small:

- `section`
- `text`
- `media_text`
- `media_full`
- `gallery`
- `query_gallery`
- `comparison`
- `credits`

Contract export:

```json
{
  "id": "block:broadies",
  "type": "media_text",
  "title": "Broadie's anchors the block",
  "body": "Broadie's gives the story its opening business identity.",
  "media": [{ "ref": "openfoto:item:FI0022551" }],
  "when": {
    "start": "1900",
    "end": "1950",
    "label": "early 1900s to 1950s",
    "precision": "year"
  }
}
```

## Authoring Principle

Source records are owned by OpenFoto/zm. Story blocks are owned by the exhibit
author.

If the author edits a caption for a story, store it as a block override. Do not
mutate the OpenFoto/zm source record unless the user explicitly edits that
source record.

