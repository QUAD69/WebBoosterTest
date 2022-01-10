<?php

namespace App\Models;

class Catalog extends \App\Core\Mvc\Model
{
    protected int $id;
    protected string $name = '';
    protected ?int $parent_id = null;

    public const TABLE_NAME = 'catalog';
    public const COLUMN_PRIMARY = 'id';


    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getParentId(): ?int
    {
        return $this->parent_id;
    }

    public function findChildCatalogs(): array
    {
        return static::find("`parent_id` = {$this->id}");
    }
}