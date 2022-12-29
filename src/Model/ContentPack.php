<?php

namespace App\Model;

class ContentPack
{
    public function __construct(
        private string $path,
        private string $uuid,
        private string $name,
        private string $description,
        private array $version,
    ) {}

    public function getPath(): string
    {
        return $this->path;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getVersion(): array
    {
        return $this->version;
    }
}