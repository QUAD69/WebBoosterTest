<?php
declare(strict_types=1);

namespace App\Core\Http;

use function setcookie;

class Response
{
    protected int $status = 200;

    protected array $headers = [];

    protected array $cookies = [];

    protected string $content = '';

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function setCookie(string $name, mixed $value, array $options = []): void
    {
        if (is_null($value)) {
            $options['expires'] = -1;
        }

        $this->cookies[$name] = [(string) $value, $options];
    }

    public function setStatusCode(int $code): void
    {
        $this->status = $code;
    }

    public function setContentType(string $mime, string $charset = ''): void
    {
        if (empty($charset)) {
            $this->headers['Content-Type'] = $mime;
        } else {
            $this->headers['Content-Type'] = "{$mime}; charset={$charset}";
        }
    }

    public function setHeader(string $header, string $value): void
    {
        $this->headers[ucwords($header, '-')] = $value;
    }

    public function redirect(string $url, int $code = 303): void
    {
        $this->status = $code;
        $this->headers['Location'] = $url;
    }

    protected function sendHeaders(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);

            foreach ($this->headers as $header => $value) {
                if (empty($value)) continue;

                $headerLine = "{$header}: {$value}";
                header($headerLine, true);
            }

            foreach ($this->cookies as $cookie => $data) {

                setcookie($cookie, $data[0], $data[1]);
            }
        }
    }

    protected function sendContent(): void
    {
        echo $this->content;
    }

    public function send(): void
    {
        $this->sendHeaders();
        $this->sendContent();
    }
}