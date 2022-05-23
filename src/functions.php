<?php

/**
 * @param array<string, mixed> $data
 */
function render(string $template, array $data = []): string
{
    extract($data);
    ob_start();
    require $template;
    $output = ob_get_clean() ?: '';

    return $output;
}

/**
 * @param string[] $scripts
 */
function renderPage(string $body = "", array $scripts = []): string
{
    return render(__DIR__ . '/../template/page.php', [
        "body" => $body,
        "scripts" => $scripts,
    ]);
}
