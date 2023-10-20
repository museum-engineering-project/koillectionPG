<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\RuntimeExtensionInterface;

class TranslationRuntime implements RuntimeExtensionInterface
{
    public function __construct(
    ) {
    }

    public function transMlang(?string $text, string $locale): string
    {
        if ($text === null) {
            return "";
        }
        
        $locale = preg_quote($locale);

        $pattern = "/{mlang " . $locale . "}.*?{mlang}/";
        $matches = [];
        preg_match_all($pattern, $text, $matches);

        // remove mlang tags of matched locale while keeping their content
        foreach ($matches[0] as &$match) {
            $originalMatch = $match;
            
            $match = str_replace("{mlang " . $locale . "}", "", $match);
            $match = str_replace("{mlang}", "", $match);
            
            $text = str_replace($originalMatch, $match, $text);
        }

        // remove all remaining (unmatched) mlang tags and their content
        $text = preg_replace("/{mlang .*?}.*?{mlang}/", '', $text);

        return $text;
    }
}
