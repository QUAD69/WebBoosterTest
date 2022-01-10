<?php
declare(strict_types=1);

namespace App\Core;

class Config
{
    public function __construct(array $config)
    {
        foreach ($config as $key => $value) {
            $this->{$key} = is_array($value) ? new static($value) : $value;
        }
    }

    /**
     * @param string $name
     * @return Config|null
     */
    public function __get(string $name): mixed
    {
        return null;
    }

    public function toArray(): array
    {
        return (array) $this;
    }

    public function toJson(): string
    {
        return json_encode($this) ?: '';
    }
}