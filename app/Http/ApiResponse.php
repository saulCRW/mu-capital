<?php

namespace App\Http;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Formato único de la API interna (/api/v1, PROYECTO.md sección 2):
 *
 *   { "ok": true,  "data": ...,   "error": null }
 *   { "ok": false, "data": null,  "error": { "code": "no_encontrado", "message": "..." } }
 */
final class ApiResponse
{
    /** @param  array<string, mixed>  $extra  claves adicionales (por ejemplo, la paginación) */
    public static function ok(mixed $data, int $status = 200, array $extra = []): JsonResponse
    {
        if ($data instanceof JsonResource) {
            $data = $data->resolve(request());
        }

        return response()->json(['ok' => true, 'data' => $data, 'error' => null] + $extra, $status);
    }

    /** @param  array<string, mixed>  $detalle  información extra del error (por ejemplo, los campos inválidos) */
    public static function error(string $codigo, string $mensaje, int $status, array $detalle = []): JsonResponse
    {
        return response()->json(['ok' => false, 'data' => null, 'error' => ['code' => $codigo, 'message' => $mensaje] + $detalle], $status);
    }

    /** Convierte cualquier excepción de una petición /api/* al formato de error. */
    public static function desdeExcepcion(Throwable $e, Request $request): ?JsonResponse
    {
        if (! $request->is('api/*')) {
            return null;
        }

        return match (true) {
            $e instanceof AuthenticationException => self::error('no_autenticado', 'Inicia sesión para usar la API.', 401),
            $e instanceof AuthorizationException => self::error('prohibido', 'No tienes permiso para esta acción.', 403),
            $e instanceof ModelNotFoundException => self::error('no_encontrado', 'El recurso no existe.', 404),
            $e instanceof ValidationException => self::error('validacion', collect($e->errors())->flatten()->first() ?? 'Datos no válidos.', 422, ['campos' => $e->errors()]),
            $e instanceof HttpExceptionInterface => self::error(match ($e->getStatusCode()) {
                403 => 'prohibido',
                404 => 'no_encontrado',
                405 => 'metodo_no_permitido',
                429 => 'demasiadas_solicitudes',
                default => 'error_http',
            }, match ($e->getStatusCode()) {
                404 => 'El recurso no existe.',
                429 => 'Demasiadas solicitudes; espera un momento.',
                default => $e->getMessage() ?: 'No se pudo atender la solicitud.',
            }, $e->getStatusCode()),
            default => self::error('error_interno', config('app.debug') ? $e->getMessage() : 'Ocurrió un error inesperado.', 500),
        };
    }
}
