<?php

namespace App\Validator\Asset;

use App\Action\Asset\BaseAction;
use App\Entity\Asset as Entity;
use App\Enum\AssetCategory as Category;
use App\Enum\AssetState;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that state change is valid.
 *
 * @author Ben Brooksnieder
 */
class StateValidator extends ConstraintValidator
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * @param State $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (null === $value || '' === $value) {
            return;
        }

        // TODO check for correct type of value
        /** @var Entity $asset */
        $asset = $value[0];
        /** @var BaseAction $action */
        $action = $value[1];
        $state = $action->getNewState();

        // first check if object is editable
        if (!$asset->isEditable()) {
            $this->context->buildViolation($constraint->uneditableState)
                ->addViolation();

            return;
        }

        // if no state change is wished, return
        if ($state === null) {
            return;
        }

        // second check if state has change (with exceptions)
        if (!\in_array($state, AssetState::getAllowedOverrideStates())) {
            if ($asset->getState() == $state) {
                $this->context->buildViolation($constraint->sameState)
                    ->addViolation();

                return;
            }
        }

        // regular state transition check
        switch ($state) {
            case AssetState::Cleaned:
                if (Category::Hdd != $asset->getCategory()) {
                    $this->context->buildViolation('asset.state.cleaned.not_hdd')
                    ->addViolation();
                }
                else if ($asset->getImages()->isEmpty()) {
                    $this->context->buildViolation('asset.state.cleaned.empty')
                    ->addViolation();
                }
                break;
            case AssetState::Reserved:
                if ($asset->getReservedBy()) {
                    $this->context->buildViolation('asset.state.reserved.still_reserved')
                    ->addViolation();
                }
                break;
            case AssetState::StoredInContainer:
                // allow relocating of asset
                // if ($asset->getLocation()) {
                //     $this->context->buildViolation('asset.state.stored_in_container.still_stored')
                //     ->addViolation();
                // }
                break;
            case AssetState::PulledOutOfContainer:
                if (!$asset->getLocation()) {
                    $this->context->buildViolation('asset.state.pulled_out_of_container.not_stored')
                    ->addViolation();
                }
                break;
            case AssetState::AssignedCase:
                if ($asset->getCase()) {
                    $this->context->buildViolation('asset.state.added_to_case.still_assigned')
                    ->addViolation();
                }
                break;
            case AssetState::RemovedFromCase:
                if (!$asset->getCase()) {
                    $this->context->buildViolation('asset.state.removed_from_case.not_assigned')
                    ->addViolation();
                }
                break;
            case AssetState::UnbindReservation:
                if (!$asset->getReservedBy()) {
                    $this->context->buildViolation('asset.state.unbind_reservation.not_reserved')
                    ->addViolation();
                }
                break;
            case AssetState::SavedImage:
                if (!$asset->isHddImageSource()) {
                    $this->context->buildViolation('asset.state.saved_image.invalid_source')
                    ->addViolation();
                }
                break;
            default:
                break;
        }
    }
}
