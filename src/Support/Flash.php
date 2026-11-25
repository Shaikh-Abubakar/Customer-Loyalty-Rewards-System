<?php
declare(strict_types=1);

namespace App\Support;

final class Flash
{
    public static function set(string $type, string $message): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }
        if (!in_array($type, ['success', 'info', 'warning', 'error'], true)) {
            $type = 'info';
        }
        $_SESSION['_flash'] = ['type' => $type, 'message' => $message];
    }

    /** @return array{type:string,message:string}|null */
    public static function consume(): ?array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }
        $flash = $_SESSION['_flash'] ?? null;
        unset($_SESSION['_flash']);
        if (!is_array($flash) || !isset($flash['type'], $flash['message'])) {
            return null;
        }
        if (!in_array($flash['type'], ['success', 'info', 'warning', 'error'], true)
            || !is_string($flash['message'])) {
            return null;
        }
        return ['type' => $flash['type'], 'message' => $flash['message']];
    }
}
