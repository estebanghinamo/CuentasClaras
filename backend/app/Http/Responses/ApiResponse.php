<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiResponse
{
    public static function ok(mixed $data, int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $data], $status);
    }

    public static function xlsx(string $content, string $filename): Response
    {
        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public static function created(mixed $data): JsonResponse
    {
        return self::ok($data, 201);
    }

    public static function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    public static function paginated(array $items, array $meta): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $items, 'meta' => $meta], 200);
    }

    public static function error(
        string $code,
        string $message,
        int $status,
        ?array $details = null,
        ?array $debug = null,
    ): JsonResponse {
        $error = [
            'code' => $code,
            'message' => $message,
            'details' => $details,
        ];

        if ($debug !== null) {
            $error['debug'] = $debug;
        }

        return response()->json(['success' => false, 'error' => $error], $status);
    }
}
