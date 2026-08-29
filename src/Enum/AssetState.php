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

    /**
     * Maps states to bootstrap color label.
     */
    public function bootstrapColor(): string
    {
        return match ($this) {
            self::Added => 'success',
            self::Cleaned => 'success',
            self::TakenToCustomer => 'default',
            self::Destroyed => 'warning',
            self::HandoverPerson => 'default',
            self::Reserved => 'primary',
            self::Lost => 'danger',
            self::StoredInContainer => 'primary',
            self::PulledOutOfContainer => 'default',
            self::AssignedCase => 'primary',
            self::RemovedFromCase => 'default',
            self::UnbindReservation => 'default',
            self::Used => 'primary',
            self::Edited => 'primary',
            self::SavedImage => 'default',
        };
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
            self::Added => 'enum.asset_state.added',
            self::Cleaned => 'enum.asset_state.cleaned',
            self::TakenToCustomer => 'enum.asset_state.taken_to_customer',
            self::Destroyed => 'enum.asset_state.destroyed',
            self::HandoverPerson => 'enum.asset_state.handover_person',
            self::Reserved => 'enum.asset_state.reserved',
            self::Lost => 'enum.asset_state.lost',
            self::StoredInContainer => 'enum.asset_state.stored_in_container',
            self::PulledOutOfContainer => 'enum.asset_state.pulled_out_of_container',
            self::AssignedCase => 'enum.asset_state.assigned_case',
            self::RemovedFromCase => 'enum.asset_state.removed_from_case',
            self::UnbindReservation => 'enum.asset_state.unbind_reservation',
            self::Used => 'enum.asset_state.used',
            self::Edited => 'enum.asset_state.edited',
            self::SavedImage => 'enum.asset_state.saved_image',
        };
    }
}
