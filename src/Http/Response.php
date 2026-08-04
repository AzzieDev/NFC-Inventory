<?php
/**
 * NFC Inventory — Physical-to-Digital State Tracker
 * Zero-Bloat Native HTTP Response Wrapper
 */
declare(strict_types=1);

namespace App\Http;

class Response
{
    public function __construct(
        public int $statusCode = 200,
        public string $body = '',
        public array $headers = []
    ) {}

    public static function html(string $html, int $status = 200): self
    {
        return new self($status, $html, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public static function json(string $json, int $status = 200): self
    {
        return new self($status, $json, ['Content-Type' => 'application/json; charset=utf-8']);
    }

    public static function redirect(string $url, int $status = 302): self
    {
        return new self($status, '', ['Location' => $url]);
    }

    /**
     * Emit HTTP response headers and output body to web server client
     */
    public function emit(): void
    {
        if (!headers_sent()) {
            http_response_code($this->statusCode);
            foreach ($this->headers as $header => $value) {
                header(sprintf('%s: %s', $header, $value));
            }
        }
        
        if ($this->body !== '') {
            echo $this->body;
        }
    }

    /**
     * Helper for retrieving a specific header value (primarily for unit tests)
     */
    public function getHeader(string $name): ?string
    {
        foreach ($this->headers as $header => $value) {
            if (strcasecmp($header, $name) === 0) {
                return $value;
            }
        }
        return null;
    }
}
