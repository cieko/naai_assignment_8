<?php
session_start();

require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/schema.php';


function currentPage(): string
{
    return $_GET['page'] ?? 'dashboard';
}

function isActive(string $page): string
{
    return currentPage() === $page ? 'active' : '';
}

function isCurrentPage(string $page): bool
{
    return currentPage() === $page;
}

function pageUrl(array $params = [], ?string $page = null): string
{
    $query = array_merge(
        ['page' => $page ?? currentPage()],
        $params
    );

    $query = array_filter(
        $query,
        static fn($value): bool => $value !== null && $value !== ''
    );

    return '?' . http_build_query($query);
}

function redirectToPage(string $page, array $params = []): void
{
    header('Location: ' . pageUrl($params, $page));
    exit;
}

function normalizeTextInput(?string $value): string
{
    $normalized = preg_replace(
        '/\s+/',
        ' ',
        trim((string)$value)
    );

    return $normalized ?? '';
}

function setFlashMessage(
    string $type,
    string $message,
    string $key = 'app_flash'
): void {
    $_SESSION[$key] = [
        'type' => $type,
        'message' => $message,
    ];
}

function pullFlashMessage(string $key = 'app_flash'): ?array
{
    $flash = $_SESSION[$key] ?? null;

    unset($_SESSION[$key]);

    return is_array($flash) ? $flash : null;
}

function setSessionFormData(string $key, array $data): void
{
    $_SESSION[$key] = $data;
}

function pullSessionFormData(string $key): array
{
    $data = $_SESSION[$key] ?? [];

    unset($_SESSION[$key]);

    return is_array($data) ? $data : [];
}

function executeStatementSafely(mysqli_stmt $stmt): array
{
    try {
        mysqli_stmt_execute($stmt);

        return [
            'success' => true,
            'error_code' => 0,
        ];
    } catch (mysqli_sql_exception $exception) {
        return [
            'success' => false,
            'error_code' => (int)$exception->getCode(),
        ];
    }
}
