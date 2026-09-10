<?php

declare(strict_types=1);

namespace App\Core\Pagination\Concerns;

use App\Core\Pagination\CursorPaginationResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

trait CursorPaginates
{
    /**
     * @return array{
     *     data: array<int, mixed>,
     *     links: array{first: ?string, last: ?string, prev: ?string, next: ?string},
     *     meta: array{path: string, per_page: int, next_cursor: ?string, prev_cursor: ?string}
     * }
     */
    protected function cursorPaginate(Builder $query, Request $request, int $defaultPerPage = 25): array
    {
        $perPage = (int) $request->input('per_page', $defaultPerPage);

        if ($perPage < 1 || $perPage > 100) {
            throw ValidationException::withMessages([
                'per_page' => ['The per page field must be between 1 and 100.'],
            ]);
        }

        $paginator = $query->cursorPaginate($perPage)->withPath($request->url());

        return CursorPaginationResponse::from($paginator, $request);
    }
}
