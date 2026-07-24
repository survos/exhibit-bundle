# OpenFoto and zm Authoring

OpenFoto and zm should provide source material for stories through bookmarks and
saved searches. Editors should not be forced to create final render output while
collecting material.

## Bookmark Authoring

A bookmark records that an editor selected a resource for possible use.

Bundle-side entity fields:

- local id
- story id
- provider
- external ref
- label
- note
- tags
- pinned flag
- sort order

Contract export:

```json
{
  "id": "bookmark:FI0021005",
  "ref": "openfoto:item:FI0021005",
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

