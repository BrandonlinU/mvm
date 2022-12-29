<?php

namespace App\Util;

use App\Model\IconContentPack;
use Symfony\Component\HttpFoundation\Response;

final class IconUtils
{
    public static function makeIconResponse(IconContentPack $icon): Response
    {
        return new Response($icon->getContent(), headers: ['Content-Type' => $icon->getMimeType()]);
    }
}