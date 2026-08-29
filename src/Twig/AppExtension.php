<?php

// AM-System
// Copyright (C) 2019 Robert Krasowski
// This program was created during an internship at DigiTrace GmbH
// Read LIZENZ.txt for full notice

// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.

namespace App\Twig;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    private $generator;

    public function __construct(UrlGeneratorInterface $generator)
    {
        $this->generator = $generator;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('enum', [$this, 'enum']),
        ];
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('barcodelinker', [$this, 'barcodeLinker']),
            new TwigFilter('caselinker', [$this, 'caseLinker']),
        ];
    }

    public function barcodeLinker($text)
    {   // Matches "DTHW00000 " -> OUTPUT "DTHW00000"
        $pattern = "/(?J)(?<barcode>(DT(HW|AS|HD|AK))\d{5})+/";

        $text = preg_replace_callback(
            $pattern,
            function ($match) {
                return '<a href="'.$this->generator->generate('details_asset', ['id' => $match['barcode']]).'">'.$match['barcode'].'</a>';
            },
            $text
        );

        return $text;
    }

    public function caseLinker($text)
    {
        $pattern = "/(?J)(?<case>(DTFA)\d{0,8})+/";

        $text = preg_replace_callback(
            $pattern,
            function ($match) {
                return '<a href="'.$this->generator->generate('detail_case', ['id' => $match['case']]).'">'.$match['case'].'</a>';
            },
            $text
        );

        return $text;
    }

    /**
     * Helper function for enums. Becomes with Twig>=3.15 obsolete because there is a built-in function.
     *
     * @see https://github.com/twigphp/Twig/issues/3681#issuecomment-1159029881
     *
     * @throws \InvalidArgumentException
     */
    public function enum(string $fullClassName): object
    {
        $parts = explode('::', $fullClassName);
        $className = $parts[0];
        $constant = $parts[1] ?? null;

        if (!enum_exists($fullClassName)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not an enum.', $className));
        }

        if ($constant) {
            return constant($fullClassName);
        }

        return new class($fullClassName) {
            public function __construct(private string $fullClassName)
            {
            }

            public function __call(string $caseName, array $arguments): mixed
            {
                if (method_exists($this->fullClassName, $caseName)) {
                    return $this->fullClassName::$caseName(...$arguments);
                }

                return constant($this->fullClassName."::$caseName");
            }
        };
    }
}
