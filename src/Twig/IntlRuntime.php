<?php

declare(strict_types=1);

namespace App\Twig;

use Symfony\Component\Intl\Countries;
use Twig\Extension\RuntimeExtensionInterface;

class IntlRuntime implements RuntimeExtensionInterface
{
    public function getEmojiFlag(string $countryCode): string
    {
        $regionalOffset = 0x1F1A5;

        return mb_chr($regionalOffset + mb_ord($countryCode[0], 'UTF-8'), 'UTF-8')
            . mb_chr($regionalOffset + mb_ord($countryCode[1], 'UTF-8'), 'UTF-8');
    }

    public function getCountriesList(): array
    {
        $countries = [];

        foreach (Countries::getNames() as $countryCode => $name) {
            $countries[] = [
                'name' => $name,
                'code' => $countryCode,
                'flag' => $this->getEmojiFlag($countryCode)
            ];
        }

        return $countries;
    }

    public function getCountryName(string $code): string
    {
        return Countries::getName($code);
    }

    public function getCountryFlag(string $code): string
    {
        return $this->getEmojiFlag($code);
    }
}
