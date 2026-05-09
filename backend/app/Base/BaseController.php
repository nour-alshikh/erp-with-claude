<?php

namespace App\Base;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller;

abstract class BaseController extends Controller
{
    use AuthorizesRequests;

    protected function success(mixed $data = null, int $status = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json(['data' => $data], $status);
    }

    protected function created(mixed $data): \Illuminate\Http\JsonResponse
    {
        return $this->success($data, 201);
    }

    protected function noContent(): \Illuminate\Http\JsonResponse
    {
        return response()->json(null, 204);
    }
}
