<?php
/**
 * NFC Inventory — Physical-to-Digital State Tracker
 * Lightweight OAuth2 OpenID Connect (OIDC) Authentication Controller with PKCS#12 (.p12) and PEM mTLS Support
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Config;
use App\Http\Response;

class AuthController
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start(['cookie_lifetime' => 2592000, 'gc_maxlifetime' => 2592000]);
        }
    }

    /**
     * Initiate OAuth2 Authorization Code Flow redirect to OIDC Identity Provider (GET /login)
     */
    public function login(array $params = [], string $basePath = ''): Response
    {
        $clientId = Config::get('OAUTH_CLIENT_ID', '');
        $authorizeUrl = Config::get('OAUTH_AUTHORIZE_URL', 'https://auth.example.com/oauth/authorize');
        $redirectUri = Config::get('OAUTH_REDIRECT_URI', 'https://inventory.example.com/login/callback');

        if (empty($clientId) || $clientId === 'your-oidc-client-id') {
            return Response::html('<h1>Configuration Required</h1><p>Please define valid OAuth2 / OIDC OAUTH_CLIENT_ID and OAUTH_CLIENT_SECRET credentials in <code>secrets.php</code>.</p>', 500);
        }

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'state' => bin2hex(random_bytes(16))
        ]);

        $_SESSION['oauth_state'] = $query;
        return Response::redirect(rtrim((string) $authorizeUrl, '?') . '?' . $query, 302);
    }

    /**
     * Process OAuth2 authorization code callback (GET /login/callback)
     */
    public function callback(array $params = [], string $basePath = ''): Response
    {
        $code = trim((string) ($_GET['code'] ?? ''));
        if ($code === '') {
            return Response::html('<h1>Login Failed</h1><p>No authorization code received from Identity Provider.</p><a href="./login">Try Again</a>', 400);
        }

        $tokenUrl = Config::get('OAUTH_TOKEN_URL', 'https://auth.example.com/oauth/token');
        $userInfoUrl = Config::get('OAUTH_USERINFO_URL', 'https://auth.example.com/oauth/userinfo');
        $clientId = Config::get('OAUTH_CLIENT_ID', '');
        $clientSecret = Config::get('OAUTH_CLIENT_SECRET', '');
        $redirectUri = Config::get('OAUTH_REDIRECT_URI', 'https://inventory.example.com/login/callback');
        $allowedUser = Config::get('OAUTH_ALLOWED_USER', 'admin');

        // 1. Exchange authorization code for access token via standard form POST body
        $postData = [
            'grant_type' => 'authorization_code',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $code,
            'redirect_uri' => $redirectUri
        ];

        $tokenResponse = $this->httpPost((string) $tokenUrl, $postData);
        $tokenJson = json_decode((string) $tokenResponse['body'], true);

        $accessToken = $tokenJson['access_token'] ?? null;
        if (!$accessToken && !Config::isEmergencyOverride()) {
            $errorDetails = sprintf(
                "HTTP Status: %s\ncURL Diagnostics: %s\nRaw Response Body:\n%s",
                $tokenResponse['http_code'] ?: 'N/A',
                $tokenResponse['error'] ?: 'None',
                $tokenResponse['body'] !== '' ? $tokenResponse['body'] : '<Empty Response>'
            );

            $html = '
            <!DOCTYPE html>
            <html lang="en" class="dark">
            <head>
                <meta charset="UTF-8">
                <title>OAuth Token Exchange Error</title>
                <script src="https://cdn.tailwindcss.com"></script>
                <style>body { background-color: #0b0d14; }</style>
            </head>
            <body class="text-slate-100 p-8 flex items-center justify-center min-h-screen">
                <main class="max-w-2xl w-full mx-auto bg-slate-900 border border-red-500/30 rounded-2xl p-6 space-y-4 shadow-xl">
                    <h1 class="text-2xl font-bold text-red-400">OAuth Token Error</h1>
                    <p class="text-slate-300 text-sm">Cloudflare mTLS authorization succeeded, but the Identity Provider declined the token exchange request.</p>
                    <div class="bg-slate-950 border border-slate-800 rounded-xl p-4 overflow-x-auto">
                        <span class="text-xs text-slate-500 uppercase tracking-wider block mb-1">Diagnostic Output</span>
                        <pre class="text-xs text-amber-300 font-mono whitespace-pre-wrap">' . htmlspecialchars($errorDetails, ENT_QUOTES) . '</pre>
                    </div>
                    <a href="javascript:history.back()" class="inline-block text-xs bg-slate-800 text-white px-4 py-2 rounded-lg hover:bg-slate-700 transition">&larr; Back to Login</a>
                </main>
            </body>
            </html>';

            return Response::html($html, 401);
        }

        // 2. Fetch authenticated user profile identity from userinfo endpoint
        $userInfoResponse = $this->httpGet((string) $userInfoUrl, (string) $accessToken);
        $userJson = json_decode((string) $userInfoResponse['body'], true);

        // Check various standard OpenID claim fields for user identity
        $username = strtolower((string) ($userJson['preferred_username'] ?? ($userJson['username'] ?? ($userJson['name'] ?? ($userJson['email'] ?? '')))));
        
        // 3. Authorize against permitted admin username identity
        if (str_contains($username, strtolower((string) $allowedUser)) || Config::isEmergencyOverride()) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user'] = $username !== '' ? $username : 'authorized (override)';
            
            $prefix = ($basePath !== '' && $basePath !== '/') ? rtrim($basePath, '/') : '';
            return Response::redirect($prefix . '/admin', 302);
        }

        return Response::html('<h1>403 Forbidden Access</h1><p>User <strong>' . htmlspecialchars($username) . '</strong> is not authorized as an administrator.</p>', 403);
    }

    /**
     * Terminate admin session (GET /logout)
     */
    public function logout(array $params = [], string $basePath = ''): Response
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $paramsCookie = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $paramsCookie['path'], $paramsCookie['domain'], $paramsCookie['secure'], $paramsCookie['httponly']);
        }
        session_destroy();

        $prefix = ($basePath !== '' && $basePath !== '/') ? rtrim($basePath, '/') : '';
        return Response::redirect($prefix !== '' ? $prefix : '/', 302);
    }

    /**
     * Resolve Cloudflare mTLS certificate parameters (supports .p12 external files or temporary PEM strings)
     * @return array{cert_file: ?string, cert_type: string, key_file: ?string, passphrase: string, is_temp: bool}
     */
    private function prepareMtlsFiles(): array
    {
        $passphrase = (string) Config::get('OAUTH_MTLS_PASSPHRASE', '');
        $certPath   = trim((string) Config::get('OAUTH_MTLS_CERT_PATH', ''));

        if ($certPath !== '' && file_exists($certPath)) {
            $ext = strtolower(pathinfo($certPath, PATHINFO_EXTENSION));
            $certType = ($ext === 'p12' || $ext === 'pfx') ? 'P12' : 'PEM';
            $keyPath = trim((string) Config::get('OAUTH_MTLS_KEY_PATH', ''));

            return [
                'cert_file'  => $certPath,
                'cert_type'  => $certType,
                'key_file'   => ($keyPath !== '' && file_exists($keyPath)) ? $keyPath : null,
                'passphrase' => $passphrase,
                'is_temp'    => false
            ];
        }

        $certString = trim((string) Config::get('OAUTH_MTLS_CERT', ''));
        if ($certString === '') {
            return ['cert_file' => null, 'cert_type' => 'PEM', 'key_file' => null, 'passphrase' => '', 'is_temp' => false];
        }

        $tempCert = tempnam(sys_get_temp_dir(), 'mtls_cert_');
        if ($tempCert !== false) {
            file_put_contents($tempCert, $certString);
        }

        $keyString = trim((string) Config::get('OAUTH_MTLS_KEY', ''));
        $tempKey = null;
        if ($keyString !== '') {
            $tempKey = tempnam(sys_get_temp_dir(), 'mtls_key_');
            if ($tempKey !== false) {
                file_put_contents($tempKey, $keyString);
            }
        }

        return [
            'cert_file'  => $tempCert !== false ? $tempCert : null,
            'cert_type'  => 'PEM',
            'key_file'   => $tempKey !== false ? $tempKey : null,
            'passphrase' => $passphrase,
            'is_temp'    => true
        ];
    }

    private function cleanupMtlsFiles(array $mtls): void
    {
        if (!empty($mtls['is_temp'])) {
            if (!empty($mtls['cert_file']) && file_exists($mtls['cert_file'])) {
                @unlink($mtls['cert_file']);
            }
            if (!empty($mtls['key_file']) && file_exists($mtls['key_file'])) {
                @unlink($mtls['key_file']);
            }
        }
    }

    /**
     * Helper to execute HTTP POST using cURL or fallback stream wrapper with Cloudflare mTLS support
     * @return array{body: string, error: string, http_code: int|string}
     */
    private function httpPost(string $url, array $data, array $extraHeaders = []): array
    {
        $body = http_build_query($data);
        $mtls = $this->prepareMtlsFiles();
        $headers = array_merge(['Accept: application/json'], $extraHeaders);

        try {
            if (function_exists('curl_init')) {
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);

                if ($mtls['cert_file'] !== null) {
                    curl_setopt($ch, CURLOPT_SSLCERT, $mtls['cert_file']);
                    curl_setopt($ch, CURLOPT_SSLCERTTYPE, $mtls['cert_type']);
                    if ($mtls['key_file'] !== null) {
                        curl_setopt($ch, CURLOPT_SSLKEY, $mtls['key_file']);
                        curl_setopt($ch, CURLOPT_SSLKEYTYPE, "PEM");
                    }
                    if ($mtls['passphrase'] !== '') {
                        curl_setopt($ch, CURLOPT_KEYPASSWD, $mtls['passphrase']);
                        if (defined('CURLOPT_SSLCERTPASSWD')) {
                            curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $mtls['passphrase']);
                        }
                    }
                }

                $result = curl_exec($ch);
                $error  = curl_error($ch);
                $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                return [
                    'body' => (string) $result,
                    'error' => (string) $error,
                    'http_code' => $status
                ];
            }

            $sslOpts = [];
            if ($mtls['cert_file'] !== null) {
                $sslOpts['local_cert'] = $mtls['cert_file'];
                if ($mtls['key_file'] !== null) {
                    $sslOpts['local_pk'] = $mtls['key_file'];
                }
                if ($mtls['passphrase'] !== '') {
                    $sslOpts['passphrase'] = $mtls['passphrase'];
                }
            }

            $headerStr = "Content-type: application/x-www-form-urlencoded\r\nAccept: application/json\r\n";
            foreach ($extraHeaders as $h) {
                $headerStr .= $h . "\r\n";
            }

            $context = stream_context_create([
                'http' => [
                    'method'  => 'POST',
                    'header'  => $headerStr,
                    'content' => $body,
                    'timeout' => 15,
                    'ignore_errors' => true
                ],
                'ssl' => $sslOpts
            ]);
            
            $result = @file_get_contents($url, false, $context);
            $httpCode = 0;
            if (!empty($http_response_header) && isset($http_response_header[0])) {
                preg_match('/HTTP\/\S+\s+(\d+)/', $http_response_header[0], $matches);
                $httpCode = isset($matches[1]) ? (int) $matches[1] : 0;
            }

            return [
                'body' => (string) $result,
                'error' => $result === false ? 'Stream context execution failure' : '',
                'http_code' => $httpCode
            ];
        } finally {
            $this->cleanupMtlsFiles($mtls);
        }
    }

    /**
     * Helper to execute HTTP GET with Bearer access token using cURL or fallback stream wrapper with mTLS support
     * @return array{body: string, error: string, http_code: int|string}
     */
    private function httpGet(string $url, string $token): array
    {
        $header = "Authorization: Bearer " . $token . "\r\nAccept: application/json\r\n";
        $mtls = $this->prepareMtlsFiles();

        try {
            if (function_exists('curl_init')) {
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);

                if ($mtls['cert_file'] !== null) {
                    curl_setopt($ch, CURLOPT_SSLCERT, $mtls['cert_file']);
                    curl_setopt($ch, CURLOPT_SSLCERTTYPE, $mtls['cert_type']);
                    if ($mtls['key_file'] !== null) {
                        curl_setopt($ch, CURLOPT_SSLKEY, $mtls['key_file']);
                        curl_setopt($ch, CURLOPT_SSLKEYTYPE, "PEM");
                    }
                    if ($mtls['passphrase'] !== '') {
                        curl_setopt($ch, CURLOPT_KEYPASSWD, $mtls['passphrase']);
                        if (defined('CURLOPT_SSLCERTPASSWD')) {
                            curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $mtls['passphrase']);
                        }
                    }
                }

                $result = curl_exec($ch);
                $error  = curl_error($ch);
                $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                return [
                    'body' => (string) $result,
                    'error' => (string) $error,
                    'http_code' => $status
                ];
            }

            $sslOpts = [];
            if ($mtls['cert_file'] !== null) {
                $sslOpts['local_cert'] = $mtls['cert_file'];
                if ($mtls['key_file'] !== null) {
                    $sslOpts['local_pk'] = $mtls['key_file'];
                }
                if ($mtls['passphrase'] !== '') {
                    $sslOpts['passphrase'] = $mtls['passphrase'];
                }
            }

            $context = stream_context_create([
                'http' => [
                    'method'  => 'GET',
                    'header'  => $header,
                    'timeout' => 15,
                    'ignore_errors' => true
                ],
                'ssl' => $sslOpts
            ]);
            $result = @file_get_contents($url, false, $context);
            $httpCode = 0;
            if (!empty($http_response_header) && isset($http_response_header[0])) {
                preg_match('/HTTP\/\S+\s+(\d+)/', $http_response_header[0], $matches);
                $httpCode = isset($matches[1]) ? (int) $matches[1] : 0;
            }

            return [
                'body' => (string) $result,
                'error' => $result === false ? 'Stream context execution failure' : '',
                'http_code' => $httpCode
            ];
        } finally {
            $this->cleanupMtlsFiles($mtls);
        }
    }
}
