<?php

namespace App\Model;

class IconContentPack
{
    public function __construct(private string $content, private string $mimeType)
    {
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }
}