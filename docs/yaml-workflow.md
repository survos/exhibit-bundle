# YAML Workflow

`survos/exhibit-bundle` should support YAML as an authoring and review format
for non-programmers.

The portable contract remains JSON. YAML is a friendlier representation of the
same shape.

## Supported Use Cases

- import a YAML draft into Symfony story entities
- export a YAML draft for editorial review
- compare YAML changes in pull requests
- publish validated JSON snapshots for renderers

## Rules

- YAML field names must match the Story Contract JSON field names.
- YAML import must validate against the Story Contract after conversion to JSON.
- Published story endpoints should return JSON by default.
- YAML comments are draft-only and are not preserved in JSON snapshots.
- Date-like strings should be quoted to avoid YAML type coercion.

## Example

```yaml
id: block:broadies
type: media_text
title: Broadie's anchors the block
body: >
  Broadie's gives the story its opening business identity.
media:
  - ref: openfoto:item:FI0022551
when:
  start: "1900"
  end: "1950"
  label: early 1900s to 1950s
  precision: year
```

## Symfony Services

Suggested services:

```text
survos_exhibit.yaml.importer
survos_exhibit.yaml.exporter
survos_exhibit.story_validator
```

The importer should parse YAML, normalize it to the Story Contract JSON shape,
validate it, then populate draft entities. The exporter should serialize current
draft state into readable YAML.

