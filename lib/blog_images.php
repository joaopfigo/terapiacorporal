<?php

function resolve_post_image(?string $imagem): string
{
    $fallback = '../iconeFinal.png';

    if (empty($imagem)) {
        return $fallback;
    }

    if (preg_match('#^https?://#i', $imagem)) {
        $context = stream_context_create([
            'http' => [
                'method'        => 'HEAD',
                'timeout'       => 5,
                'ignore_errors' => true,
            ],
            'https' => [
                'method'        => 'HEAD',
                'timeout'       => 5,
                'ignore_errors' => true,
            ],
        ]);

        $headers = @get_headers($imagem, 0, $context);
        if ($headers === false) {
            return $fallback;
        }

        $statusLine = $headers[0] ?? '';
        if (stripos($statusLine, '404') !== false) {
            return $fallback;
        }

        return $imagem;
    }

    if (strpos($imagem, '../') === 0) {
        return $imagem;
    }

    $imagem = ltrim($imagem, '/');

    return '../' . $imagem;
}
