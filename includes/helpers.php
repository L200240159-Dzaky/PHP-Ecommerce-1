<?php

declare(strict_types=1);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message === null) {
        if (!isset($_SESSION['flash'][$key])) {
            return null;
        }

        $value = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);

        return $value;
    }

    $_SESSION['flash'][$key] = $message;
    return null;
}

function old(string $key, string $default = ''): string
{
    return $_SESSION['old'][$key] ?? $default;
}

function clear_old_input(): void
{
    unset($_SESSION['old']);
}

function set_old_input(array $data): void
{
    $_SESSION['old'] = $data;
}

function user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return user() !== null;
}

function has_role(string|array $roles): bool
{
    $currentUser = user();

    if ($currentUser === null) {
        return false;
    }

    $roles = is_array($roles) ? $roles : [$roles];

    return in_array($currentUser['role'], $roles, true);
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('error', 'Please log in to continue.');
        redirect('auth/login.php');
    }
}

function require_role(string|array $roles): void
{
    require_login();

    if (!has_role($roles)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(?string $token): bool
{
    return is_string($token) && hash_equals(csrf_token(), $token);
}

function load_user_by_email(string $email): ?array
{
    $statement = db()->prepare('SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1');
    $statement->execute([$email]);
    $user = $statement->fetch();

    return $user ?: null;
}

function authenticate_user(array $user): void
{
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
    ];
}

function current_page(string $path): bool
{
    return str_contains($_SERVER['SCRIPT_NAME'] ?? '', $path);
}
