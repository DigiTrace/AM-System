<?php

namespace App\Validator;

use App\Entity\Asset as Entity;
use App\Enum\AssetCategory as Category;
use App\Enum\AssetState as State;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that state change is valid.
 * 
 * @author Ben Brooksnieder
 */
class AssetStateValidator extends ConstraintValidator
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * @param AssetState $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (null === $value || '' === $value) {
            return;
        }

        // get old entity
        /** @var Entity $asset */
        $asset = $this->context->getObject();
        $unitOfWork = $this->entityManager->getUnitOfWork();
        $original = $unitOfWork->getOriginalEntityData($asset);

        // first check if object is editable
        if (isset($original['state']) && !$original['state']->isEditable()) {
            $this->context->buildViolation($constraint->uneditableState)
                ->addViolation();

            return;
        }

        // second check if state has change (with exceptions)
        // TODO
        if (!\in_array($value, State::getAllowedOverrideStates())) {
            if (isset($original['state']) && $value === $original['state']) {
                $this->context->buildViolation($constraint->sameState)
                    ->addViolation();

                return;
            }
        }

        // regular state transition check
        switch ($value) {
            case State::Cleaned:
                if (Category::Hdd != $asset->getCategory()) {
                    $this->context->buildViolation('asset.state.cleaned.not_hdd')
                    ->addViolation();
                }
                break;
            case State::Reserved:
                if ($original && null !== $original['reservedBy']) {
                    $this->context->buildViolation('asset.state.reserved.still_reserved')
                    ->addViolation();
                }
                break;
            case State::StoredInContainer:
                if ($original && null !== $original['location']) {
                    $this->context->buildViolation('asset.state.stored_in_container.still_stored')
                    ->addViolation();
                }
                break;
            case State::PulledOutOfContainer:
                if ($original && null === $original['location']) {
                    $this->context->buildViolation('asset.state.pulled_out_of_container.not_stored')
                    ->addViolation();
                }
                break;
            case State::AssignedCase:
                if ($original && null !== $original['case']) {
                    $this->context->buildViolation('asset.state.added_to_case.still_assigned')
                    ->addViolation();
                }
                break;
            case State::RemovedFromCase:
                if ($original && null === $original['case']) {
                    $this->context->buildViolation('asset.state.removed_from_case.not_assigned')
                    ->addViolation();
                }
                break;
            case State::UnbindReservation:
                if ($original && null === $original['reservedBy']) {
                    $this->context->buildViolation('asset.state.unbind_reservation.not_reserved')
                    ->addViolation();
                }
                break;
            case State::SavedImage:
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
