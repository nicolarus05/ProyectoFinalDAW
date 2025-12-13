<?php

/**
 * Helper para cargar assets de Vite en contexto multi-tenancy
 * Los assets compilados están en public/build/ y se sirven desde el dominio actual
 * 
 * IMPORTANTE: Usa el dominio actual para evitar problemas de CORS
 */
if (!function_exists('vite_asset')) {
    function vite_asset(array $entrypoints): string
    {
        // Usar el dominio actual (tenant o central) para evitar CORS
        // Los assets están físicamente en public/build/ accesibles desde cualquier dominio
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:90';
        $baseUrl = $protocol . '://' . $host;
        
        // Usar __DIR__ para obtener ruta correcta tanto en local como en Docker
        // __DIR__ es app/Helpers, necesitamos subir 2 niveles para llegar a la raíz
        $projectRoot = dirname(dirname(__DIR__));
        $manifestPath = $projectRoot . '/public/build/manifest.json';
        
        if (!file_exists($manifestPath)) {
            return '<!-- Manifest no encontrado en: ' . $manifestPath . ' -->';
        }
        
        $manifest = json_decode(file_get_contents($manifestPath), true);
        
        if (!$manifest) {
            return '<!-- Error al leer manifest.json -->';
        }
        
        $html = '';
        
        foreach ($entrypoints as $entry) {
            if (!isset($manifest[$entry])) {
                continue;
            }
            
            $file = $manifest[$entry];
            
            // Determinar si el archivo principal es CSS o JS por su extensión
            $isMainCss = isset($file['file']) && str_ends_with($file['file'], '.css');
            
            // Agregar archivo principal
            if (isset($file['file'])) {
                $url = $baseUrl . '/build/' . $file['file'];
                
                if ($isMainCss) {
                    // Es un archivo CSS directo
                    $html .= '<link rel="stylesheet" href="' . htmlspecialchars($url) . '">' . PHP_EOL;
                } else {
                    // Es un archivo JS
                    $html .= '<script type="module" src="' . htmlspecialchars($url) . '"></script>' . PHP_EOL;
                }
            }
            
            // Agregar CSS adicionales (solo si no es el archivo principal)
            if (!$isMainCss && isset($file['css'])) {
                foreach ($file['css'] as $css) {
                    $url = $baseUrl . '/build/' . $css;
                    $html .= '<link rel="stylesheet" href="' . htmlspecialchars($url) . '">' . PHP_EOL;
                }
            }
        }
        
        return $html;
    }
}

/**
 * Sanitiza entrada HTML para prevenir XSS
 * 
 * @param string|null $html El HTML a sanitizar
 * @param string|null $allowedTags Etiquetas HTML permitidas (formato: '<p><br><strong>')
 * @return string El HTML sanitizado
 */
if (!function_exists('sanitize_html')) {
    function sanitize_html($html, $allowedTags = null)
    {
        if ($html === null) {
            return '';
        }
        
        if ($allowedTags === null) {
            // Por defecto, eliminar todas las etiquetas
            return strip_tags($html);
        }
        
        return strip_tags($html, $allowedTags);
    }
}

/**
 * Sanitiza input de texto simple
 * Útil para nombres, emails, etc.
 * 
 * @param string|null $input El texto a sanitizar
 * @return string El texto sanitizado
 */
if (!function_exists('sanitize_input')) {
    function sanitize_input($input)
    {
        if ($input === null) {
            return '';
        }
        
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Sanitiza y valida un número de teléfono
 * 
 * @param string|null $phone El teléfono a sanitizar
 * @return string El teléfono sanitizado
 */
if (!function_exists('sanitize_phone')) {
    function sanitize_phone($phone)
    {
        if ($phone === null) {
            return '';
        }
        
        // Permitir solo números, espacios, guiones, paréntesis y +
        return preg_replace('/[^0-9+\s\-()]/', '', trim($phone));
    }
}
