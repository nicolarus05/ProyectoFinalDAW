<?php

namespace App\Traits;

/**
 * Trait HasCrudMessages
 * 
 * Proporciona mensajes estandarizados para operaciones CRUD
 */
trait HasCrudMessages
{
    /**
     * Obtener nombre del recurso en singular (debe ser implementado por la clase)
     */
    protected function getResourceName(): string
    {
        // Por defecto, intenta obtenerlo del nombre del controlador
        $className = class_basename($this);
        return str_replace('Controller', '', $className);
    }

    /**
     * Mensaje de creación exitosa
     */
    protected function getCreatedMessage(): string
    {
        $resource = $this->getResourceName();
        return "{$resource} creado exitosamente.";
    }

    /**
     * Mensaje de actualización exitosa
     */
    protected function getUpdatedMessage(): string
    {
        $resource = $this->getResourceName();
        return "{$resource} actualizado exitosamente.";
    }

    /**
     * Mensaje de eliminación exitosa
     */
    protected function getDeletedMessage(): string
    {
        $resource = $this->getResourceName();
        return "{$resource} eliminado exitosamente.";
    }

    /**
     * Mensaje de recurso no encontrado
     */
    protected function getNotFoundMessage(): string
    {
        $resource = $this->getResourceName();
        return "{$resource} no encontrado.";
    }

    /**
     * Mensaje de error al crear
     */
    protected function getCreateErrorMessage(): string
    {
        $resource = $this->getResourceName();
        return "Error al crear {$resource}.";
    }

    /**
     * Mensaje de error al actualizar
     */
    protected function getUpdateErrorMessage(): string
    {
        $resource = $this->getResourceName();
        return "Error al actualizar {$resource}.";
    }

    /**
     * Mensaje de error al eliminar
     */
    protected function getDeleteErrorMessage(): string
    {
        $resource = $this->getResourceName();
        return "Error al eliminar {$resource}.";
    }
}
