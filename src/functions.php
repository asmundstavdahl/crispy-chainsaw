<?php

function render(string $template, array $data = []): string
{
    extract($data);
    ob_start();
    require $template;
    $output = ob_get_clean();

    return $output;
}

function renderPage(string $body = "", $scripts = []): string
{
    return render(__DIR__ . '/../template/page.php', [
        "body" => $body,
        "scripts" => $scripts,
    ]);
}