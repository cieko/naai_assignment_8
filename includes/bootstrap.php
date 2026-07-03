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

function getSearchTerm(string $key = 'search'): string
{
    return normalizeTextInput($_GET[$key] ?? '');
}

function formatInrAmount(float $amount): string
{
    $isNegative = $amount < 0;
    $normalizedAmount = abs($amount);
    $parts = explode('.', number_format($normalizedAmount, 2, '.', ''));
    $integerPart = $parts[0];
    $decimalPart = $parts[1] ?? '00';
    $lastThree = strlen($integerPart) > 3
        ? substr($integerPart, -3)
        : $integerPart;
    $leadingDigits = strlen($integerPart) > 3
        ? substr($integerPart, 0, -3)
        : '';

    if ($leadingDigits !== '') {
        $leadingDigits = preg_replace(
            '/\B(?=(\d{2})+(?!\d))/',
            ',',
            $leadingDigits
        ) ?? $leadingDigits;

        $integerPart = $leadingDigits . ',' . $lastThree;
    } else {
        $integerPart = $lastThree;
    }

    return ($isNegative ? '-' : '') . 'Rs. ' . $integerPart . '.' . $decimalPart;
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

function executeStatementOrFail(mysqli_stmt $stmt): void
{
    if (!mysqli_stmt_execute($stmt)) {
        throw new RuntimeException(
            mysqli_stmt_error($stmt),
            (int)mysqli_stmt_errno($stmt)
        );
    }
}

function isValidDateInput(?string $value): bool
{
    if ($value === null || $value === '') {
        return false;
    }

    $date = DateTime::createFromFormat('Y-m-d', $value);

    return $date !== false
        && $date->format('Y-m-d') === $value;
}

function runTransaction(mysqli $conn, callable $callback): array
{
    mysqli_begin_transaction($conn);

    try {
        $result = $callback();
        mysqli_commit($conn);

        return [
            'success' => true,
            'result' => $result,
            'error_code' => 0,
        ];
    } catch (mysqli_sql_exception $exception) {
        mysqli_rollback($conn);

        return [
            'success' => false,
            'result' => null,
            'error_code' => (int)$exception->getCode(),
        ];
    } catch (Exception $exception) {
        mysqli_rollback($conn);

        return [
            'success' => false,
            'result' => null,
            'error_code' => (int)$exception->getCode(),
        ];
    } catch (Throwable $throwable) {
        mysqli_rollback($conn);
        throw $throwable;
    }
}
