<?php

namespace App\Http\Controllers;

use App\Services\TrapResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrapController extends Controller
{
    public function __construct(
        private readonly TrapResponseService $trapService,
    ) {}

    /**
     * Handle a trap trigger from the PWA frontend.
     *
     * POST /traps/trigger
     * Body: { trap_code: 'SALARY_PEEK', element: '...', page_url: '...', metadata: {...} }
     */
    public function trigger(Request $request): JsonResponse
    {
        $request->validate([
            'trap_code' => ['required', 'string', 'exists:traps,trap_code'],
        ]);

        $user = $request->user();

        $result = $this->trapService->triggerTrap(
            user: $user,
            trapCode: $request->input('trap_code'),
            request: $request,
        );

        return response()->json($result);
    }
}
