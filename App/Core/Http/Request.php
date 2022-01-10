<?php
declare(strict_types=1);

namespace App\Core\Http;

class Request
{
    protected string $uri;

    protected string $method;

    protected string $protocol;

    protected array $headers;

    protected array $cookies;

    protected bool $secured;

    protected array $getData;

    protected array $postData;

    protected array $fileData;

    protected int $time;

    public function __construct(bool $from_current_request = true)
    {
        if ($from_current_request && isset($_SERVER)) {
            $this->uri          = $_SERVER['REQUEST_URI'] ?? '/';
            $this->method       = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            $this->protocol     = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
            $this->secured      = !empty($_SERVER['HTTPS']);
            $this->headers      = getallheaders();
            $this->cookies      = $_COOKIE ?? [];
            $this->time         = $_SERVER['REQUEST_TIME'] ?? time();

            // Если не удалось получить заголовки из getallheaders, пробуем прочесть их из $_SERVER
            if ($this->headers === false) {

                foreach ($_SERVER as $key => $value) {
                    if (!str_starts_with($key, 'HTTP_')) continue;

                    $header = substr($key, 5);
                    $header = strtolower($header);
                    $header = strtr($header, '_', '-');
                    $header = ucwords($header, '-');

                    $this->headers[$header] = $value;
                }

            }

            $this->getData = $_GET ?? [];
            $this->postData = $_POST ?? [];
            $this->fileData = $_FILES ?? [];

            // Если запрос пришёл в json, пробуем спарсить его
            if (empty($this->postData) && $this->isAccept('application/json')) {
                $bodyContent = file_get_contents('php://input');
                $bodyContent = json_decode($bodyContent, true, 128);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $this->postData = $bodyContent;
                }
            }
        }
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getTime(): int
    {
        return $this->time;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getProtocol(): string
    {
        return $this->protocol;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    public function getCookies(): array
    {
        return $this->cookies;
    }

    public function getCookie(string $name): ?string
    {
        return $this->cookies[$name] ?? null;
    }

    public function isSecured(): bool
    {
        return $this->secured;
    }

    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function isDelete(): bool
    {
        return $this->method === 'DELETE';
    }

    public function isPut(): bool
    {
        return $this->method === 'PUT';
    }

    public function isOptions(): bool
    {
        return $this->method === 'OPTIONS';
    }

    public function isHead(): bool
    {
        return $this->method === 'HEAD';
    }

    public function isAccept(string $mime, bool $strict = true): bool
    {
        $acceptHeader = $this->headers['Accept'] ?? '';

        if (!$strict and str_contains($acceptHeader, '*/*')) {
            return true;
        } else {
            return str_contains($acceptHeader, $mime);
        }
    }
}