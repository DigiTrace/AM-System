<?php

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Allowed states for assets (objects).
 *
 * @author Ben Brooksnieder
 */
enum AssetState: int implements TranslatableInterface
{
    case Added = 0;
    case Cleaned = 1;
    case TakenToCustomer = 2;
    case Destroyed = 3;
    case HandoverPerson = 4;
    case Reserved = 5;
    case Lost = 6;
    case StoredInContainer = 7;
    case PulledOutOfContainer = 8;
    case AssignedCase = 9;
    case RemovedFromCase = 10;
    case UnbindReservation = 11;
    case Used = 12;
    case Edited = 13;
    case SavedImage = 14;

    /**
     * Matches `status.X` string to asset state.
     * Replaces former `Objekt::$statusToId`.
     *
     * @param string $state Status string
     *
     * @return AssetState Corresponding asset state
     */
    public static function fromString(string $state): self
    {
        return match ($state) {
            'status.added' => self::Added,
            'status.cleaned' => self::Cleaned,
            'status.taken.to.customer' => self::TakenToCustomer,
            'status.destroyed' => self::Destroyed,
            'status.handover.person' => self::HandoverPerson,
            'status.reserved' => self::Reserved,
            'status.lost' => self::Lost,
            'status.stored.in.container' => self::StoredInContainer,
            'status.pulled.out.of.container' => self::PulledOutOfContainer,
            'status.added.to.case' => self::AssignedCase,
            'status.removed.from.case' => self::RemovedFromCase,
            'status.unbind.reservation' => self::UnbindReservation,
            'status.used' => self::Used,
            'status.edited' => self::Edited,
            'status.saved.image' => self::SavedImage,
            default => throw new \InvalidArgumentException("Unkown state: $state"),
        };
    }

    /**
     * Returns whether state generally allows editing.
     */
    public static function editAllowed(self $state): bool
    {
        return match ($state) {
            self::Added => true,
            self::Cleaned => true,
            self::TakenToCustomer => true,
            self::Destroyed => false,
            self::HandoverPerson => true,
            self::Reserved => true,
            self::Lost => false,
            self::StoredInContainer => true,
            self::PulledOutOfContainer => true,
            self::AssignedCase => true,
            self::RemovedFromCase => true,
            self::UnbindReservation => true,
            self::Used => true,
            self::Edited => true,
            self::SavedImage => true,
        };
    }

    /**
     * Returns list of states which belong generally to editable assets.
     *
     * @return self[]
     */
    public static function getEditableStates(): array
    {
        return array_filter(self::cases(), [self::class, 'editAllowed']);
    }

    /**
     * Returns whether this state allows editing.
     */
    public function isEditable(): bool
    {
        return self::editAllowed($this);
    }

    /**
     * Returns list of states which allow updating without changing state.
     *
     * - `self::Used`,
     * - `self::Edited`,
     * - `self::StoredInContainer`,
     * - `self::SavedImage`,
     *
     * @return self[]
     */
    public static function getAllowedOverrideStates(): array
    {
        return [
            self::StoredInContainer,
            self::Used,
            self::Edited,
            self::SavedImage,
        ];
    }

    /**
     * Returns list of states which are allow for batch asset actions.
     *
     * @return self[]
     */
    public static function getAllowedBatchActionStates(): array
    {
        return [
            self::Cleaned,
            self::TakenToCustomer,
            self::Destroyed,
            self::HandoverPerson,
            self::Reserved,
            self::Lost,
            self::StoredInContainer,
            self::PulledOutOfContainer,
            self::AssignedCase,
            self::RemovedFromCase,
            self::UnbindReservation,
            self::Used,
        ];
    }

    
    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans($this->toTranslatableString(), locale: $locale);
    }

    /**
     * Return string identifier that can be translated to state.
     *
     * @return string translation identifier
     */
    public function toTranslatableString(): string
    {
        return match ($this) {
            self::Added => 'status.added',
            self::Cleaned => 'status.cleaned',
            self::TakenToCustomer => 'status.taken.to.customer',
            self::Destroyed => 'status.destroyed',
            self::HandoverPerson => 'status.handover.person',
            self::Reserved => 'status.reserved',
            self::Lost => 'status.lost',
            self::StoredInContainer => 'status.stored.in.container',
            self::PulledOutOfContainer => 'status.pulled.out.of.container',
            self::AssignedCase => 'status.added.to.case',
            self::RemovedFromCase => 'status.removed.from.case',
            self::UnbindReservation => 'status.unbind.reservation',
            self::Used => 'status.used',
            self::Edited => 'status.edited',
            self::SavedImage => 'status.saved.image',
        };
    }
}
