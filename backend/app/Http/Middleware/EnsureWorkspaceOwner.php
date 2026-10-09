<?php

namespace App\Http\Middleware;

use App\DTOs\Workspace\WorkspaceMembershipDto;
use App\Exceptions\ForbiddenException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspaceOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var WorkspaceMembershipDto|null $membership */
        $membership = $request->attributes->get('membership');

        if ($membership === null || !$membership->isOwner()) {
            throw new ForbiddenException();
        }

        return $next($request);
    }
}
