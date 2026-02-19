<?php

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Degrees of case secrecy.
 *
 * @author Ben Brooksnieder
 */
enum CaseSecrecy: string implements TranslatableInterface
{
    case Public = "DOS_PUBLIC";
    case Internal = "DOS_INTERNAL";
    case Confidential = "DOS_CONFIDENTIAL";
    case Secret = "DOS_SECRET";


    /**
     * Maps cases to bootstrap color label.
     */
    public function bootstrapColor(): string
    {
        return match ($this) {
            self::Public => "success",
            self::Internal => "primary",
            self::Confidential => "warning",
            self::Secret => "danger",
        };
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans($this->toTranslatableString(), locale: $locale);
    }

    /**
     * Return string identifier that can be translated to category.
     *
     * @return string translation identifier
     */
    public function toTranslatableString(): string
    {
        return match ($this) {
            self::Public => "secrecy.public",
            self::Internal => "secrecy.internal",
            self::Confidential => "secrecy.confidential",
            self::Secret => "secrecy.secret",
        };
    }
}
