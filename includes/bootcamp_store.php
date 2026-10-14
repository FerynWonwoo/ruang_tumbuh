<?php
define('BOOTCAMP_FILE', __DIR__ . '/../data/bootcamp.json');

function bootcamp_load(): array
{
    if (!file_exists(BOOTCAMP_FILE)) {
        return [];
    }
    $data = json_decode(file_get_contents(BOOTCAMP_FILE), true);
    return is_array($data) ? $data : [];
}

function bootcamp_save(array $data): void
{
    $dir = dirname(BOOTCAMP_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    file_put_contents(
        BOOTCAMP_FILE,
        json_encode(array_values($data), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );
}