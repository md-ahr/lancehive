<?php

declare(strict_types=1);

namespace App\Core\Pagination;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Http\Request;

final class CursorPaginationResponse
{
    /**
     * @return array{
     *     data: array<int, mixed>,
     *     links: array{first: ?string, last: ?string, prev: ?string, next: ?string},
     *     meta: array{path: string, per_page: int, next_cursor: ?string, prev_cursor: ?string}
     * }
     */
    public static function from(CursorPaginator $paginator, Request $request): array
    {
        $perPage = $paginator->perPage();
        $path = $paginator->path();

        return [
            'data' => $paginator->items(),
            'links' => [
                'first' => $path.'?'.http_build_query(['per_page' => $perPage]),
                'last' => null,
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
            'meta' => [
                'path' => $path,
                'per_page' => $perPage,
                'next_cursor' => $paginator->nextCursor()?->encode(),
                'prev_cursor' => $paginator->previousCursor()?->encode(),
            ],
        ];
    }
}
