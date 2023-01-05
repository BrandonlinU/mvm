<?php

namespace App\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MinecraftHtmlFormatter extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('minecraft_format', $this->format(...), ['pre_escape' => 'html', 'is_safe' => ['html']])
        ];
    }

    private function format(string $text): string
    {
        $formattedText = '';
        $formats = [];

        $characters = new \ArrayIterator(mb_str_split($text));
        while($characters->valid()) {
            $character = $characters->current();
            $characters->next();

            if ($character !== '§') {
                $formattedText .= $character;
                continue;
            }

            $code = $characters->current();
            $characters->next();

            if (in_array($code, $formats, true)) {
                continue;
            }
            if ($code === 'r') {
                $formattedText .= str_repeat('</span>', count($formats));
                $formats = [];

                continue;
            }

            $formattedText .= match ($code) {
                '0' => '<span style="color: #000000;">',
                '1' => '<span style="color: #0000AA;">',
                '2' => '<span style="color: #00AA00;">',
                '3' => '<span style="color: #00AAAA;">',
                '4' => '<span style="color: #AA0000;">',
                '5' => '<span style="color: #AA00AA;">',
                '6' => '<span style="color: #FFAA00;">',
                '7' => '<span style="color: #AAAAAA;">',
                '8' => '<span style="color: #555555;">',
                '9' => '<span style="color: #5555FF;">',
                'a' => '<span style="color: #55FF55;">',
                'b' => '<span style="color: #55FFFF;">',
                'c' => '<span style="color: #FF5555;">',
                'd' => '<span style="color: #FF55FF;">',
                'e' => '<span style="color: #FFFF55;">',
                'f' => '<span style="color: #FFFFFF;">',
                'g' => '<span style="color: #DDD605;">',
                'k' => '<span style="background-color: #000; color: #000000;">',
                'l' => '<span style="font-weight: bold;">',
                'm' => '<span style="text-decoration: line-through;">',
                'n' => '<span style="text-decoration: underline;">',
                'o' => '<span style="font-style: italic;">',
                default => '',
            };

            $formats[] = $code;
        }
        $formattedText .= str_repeat('</span>', count($formats));

        return $formattedText;
    }
}