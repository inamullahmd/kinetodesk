<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfitMarginRequest;
use App\Services\ProfitService;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    public function __construct(
        private readonly ProfitService $profitService
    ) {
    }

    public function profitMargin(ProfitMarginRequest $request): JsonResponse
    {
        return response()->json(
            $this->profitService->getProfitMargin($request->validated())
        );
    }
}