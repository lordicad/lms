<?php

/**
 * Host-agnostic deploy webhook.
 *
 * Trigger:  https://<host>/deploy.php?token=<DEPLOY_TOKEN>
 *       or  send header  X-Deploy-Token: <DEPLOY_TOKEN>
 *
 * It reads DEPLOY_TOKEN straight out of ../.env (no framework boot, no Dotenv
 * library), compares it with hash_equals(), and FAILS CLOSED: if DEPLOY_TOKEN is
 * unset or empty it returns 503 and refuses to run. On a valid token it runs the
 * sibling deploy.sh and streams its output.
 *
 * Nothing host-specific lives in here. deploy.sh holds the project settings.
 */

header('Content-Type: text/plain; charset=utf-8');

$root         = dirname(__DIR__);      // repo root; public/ is the docroot
$envFile      = $root . '/.env';
$deployScript = $root . '/deploy.sh';

/**
 * Read a single key out of a .env file without booting anything.
 */
function deploy_read_env(string $file, string $key): ?string
{
    if (! is_readable($file)) {
        return null;
    }

    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = ltrim($line);

        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }

        [$k, $v] = explode('=', $line, 2);

        if (trim($k) !== $key) {
            continue;
        }

        $v = trim($v);

        // Strip a single pair of surrounding quotes.
        if (strlen($v) >= 2 && ($v[0] === '"' || $v[0] === "'") && substr($v, -1) === $v[0]) {
            $v = substr($v, 1, -1);
        }

        return $v;
    }

    return null;
}

$expected = deploy_read_env($envFile, 'DEPLOY_TOKEN');

// Fail closed: no token configured => deploy is disabled.
if ($expected === null || $expected === '') {
    http_response_code(503);
    echo "Deploy disabled: DEPLOY_TOKEN is not set in .env.\n";
    exit;
}

$provided = $_GET['token'] ?? $_SERVER['HTTP_X_DEPLOY_TOKEN'] ?? '';

if (! is_string($provided) || ! hash_equals($expected, $provided)) {
    http_response_code(403);
    echo "Forbidden.\n";
    exit;
}

// shell_exec must be available; many shared hosts disable it.
$disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

if (! function_exists('shell_exec') || in_array('shell_exec', $disabled, true)) {
    http_response_code(500);
    echo "shell_exec is disabled on this host.\n";
    echo "Run the deploy manually instead (cPanel Terminal or a cron job):\n";
    echo '  bash ' . $deployScript . "\n";
    exit;
}

if (! is_file($deployScript)) {
    http_response_code(500);
    echo "deploy.sh not found at {$deployScript}\n";
    exit;
}

// Run it from the repo root; 2>&1 so stderr is captured too.
$output = shell_exec('cd ' . escapeshellarg($root) . ' && bash ' . escapeshellarg($deployScript) . ' 2>&1');

echo $output === null
    ? "Deploy produced no output (shell_exec returned null).\n"
    : $output;
