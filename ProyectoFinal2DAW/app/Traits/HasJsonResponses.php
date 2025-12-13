<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Trait HasJsonResponses
 * 
 * Proporciona métodos consistentes para respuestas JSON
 * Estandariza la estructura de las respuestas de API
 */
trait HasJsonResponses
{
    /**
     * Respuesta JSON de éxito
     */
    protected function successResponse($data = null, string $message = 'Operación exitosa', int $code = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    /**
     * Respuesta JSON de error
     */
    protected function errorResponse(string $message = 'Error en la operación', int $code = 400, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Respuesta JSON de validación fallida
     */
    protected function validationErrorResponse($errors, string $message = 'Errores de validación', int $code = 422): JsonResponse
    {
        return $this->errorResponse($message, $code, $errors);
    }

    /**
     * Respuesta JSON no encontrado
     */
    protected function notFoundResponse(string $message = 'Recurso no encontrado'): JsonResponse
    {
        return $this->errorResponse($message, 404);
    }

    /**
     * Respuesta JSON no autorizado
     */
    protected function unauthorizedResponse(string $message = 'No autorizado'): JsonResponse
    {
        return $this->errorResponse($message, 401);
    }

    /**
     * Respuesta JSON prohibido
     */
    protected function forbiddenResponse(string $message = 'Acceso prohibido'): JsonResponse
    {
        return $this->errorResponse($message, 403);
    }

    /**
     * Respuesta JSON de creación exitosa
     */
    protected function createdResponse($data = null, string $message = 'Recurso creado exitosamente'): JsonResponse
    {
        return $this->successResponse($data, $message, 201);
    }

    /**
     * Respuesta JSON sin contenido
     */
    protected function noContentResponse(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
