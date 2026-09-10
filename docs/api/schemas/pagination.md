# Cursor Pagination Envelope

All list endpoints return Laravel cursor pagination JSON.

## Query parameters

| Param | Type | Default | Rules |
|-------|------|---------|-------|
| `per_page` | integer | 25 | min: 1, max: 100 |
| `cursor` | string | — | Opaque; from `meta.next_cursor` of previous response |

## Response shape

```json
{
  "data": [],
  "links": {
    "first": "https://example.com/api/v1/clients?per_page=25",
    "last": null,
    "prev": null,
    "next": "https://example.com/api/v1/clients?cursor=eyJpZCI6MTB9&per_page=25"
  },
  "meta": {
    "path": "https://example.com/api/v1/clients",
    "per_page": 25,
    "next_cursor": "eyJpZCI6MTB9",
    "prev_cursor": null
  }
}
```

## Field reference

| Field | Type | Description |
|-------|------|-------------|
| `data` | array | Resource objects for the current page |
| `links.first` | string \| null | URL for first page |
| `links.last` | string \| null | URL for last page (often null with cursor) |
| `links.prev` | string \| null | URL for previous page |
| `links.next` | string \| null | URL for next page |
| `meta.path` | string | Base list URL |
| `meta.per_page` | integer | Items per page |
| `meta.next_cursor` | string \| null | Pass as `?cursor=` for next page |
| `meta.prev_cursor` | string \| null | Pass as `?cursor=` for previous page |

## Usage

1. First request: `GET /clients?per_page=25`
2. Next page: `GET /clients?per_page=25&cursor={meta.next_cursor}`
3. Stop when `links.next` is `null`
