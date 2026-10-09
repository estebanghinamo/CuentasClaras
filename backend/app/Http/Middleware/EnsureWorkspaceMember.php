<?php

namespace App\Http\Middleware;

use App\Exceptions\NotFoundException;
use App\Services\Workspace\WorkspaceAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspaceMember
{
    public function __construct(private readonly WorkspaceAccessService $access)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $workspaceId = $request->route('workspaceId');

        if (!ctype_digit((string) $workspaceId)) {
            throw new NotFoundException();
        }

        $membership = $this->access->getMembership((int) $workspaceId, $request->user()->id);

        $request->attributes->set('membership', $membership);

        return $next($request);
    }
}
