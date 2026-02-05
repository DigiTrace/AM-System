<?php

namespace App\Validator;

use App\Entity\Asset as Entity;
use App\Enum\AssetCategory as Category;
use App\Enum\AssetState as State;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class AssetValidator extends ConstraintValidator
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function validate($asset, Constraint $constraint)
    {
        /* @var App\Validator\Asset $constraint */

        if (null === $asset || '' === $asset) {
            return;
        }

        // get old entity
        /** @var Entity $asset */
        $unitOfWork = $this->entityManager->getUnitOfWork();
        $original = $unitOfWork->getOriginalEntityData($asset);

        if (empty($original)) {
            return;
        }


        // regular state transition check
        switch ($asset->getState()) {
            case State::Reserved:
                if (null === $asset->getReservedBy()) {
                    $this->context->buildViolation('asset.state.reserved.not_reserved')
                    ->addViolation();
                }
                break;
            case State::StoredInContainer:
                if (null === $asset->getLocation()) {
                    $this->context->buildViolation('asset.state.stored_in_container.not_stored')
                    ->addViolation();
                }
                break;
            case State::PulledOutOfContainer:
                if (null !== $asset->getLocation()) {
                    $this->context->buildViolation('asset.state.pulled_out_of_container.still_stored')
                    ->addViolation();
                }

                break;
            case State::AssignedCase:
                if (null === $asset->getCase()) {
                    $this->context->buildViolation('asset.state.added_to_case.not_assigned')
                    ->addViolation();
                }
                break;
            case State::RemovedFromCase:
                if (!$asset->isRemovableFromCase()) {
                    $this->context->buildViolation('asset.state.removed_from_case.not_removable')
                    ->addViolation();
                }
                if (null !== $asset->getCase()) {
                    $this->context->buildViolation('asset.state.removed_from_case.still_assigned')
                    ->addViolation();
                }
                // // check original state
                // if (null === $original['case']) {
                //     $this->context->buildViolation('asset.state.removed_from_case.not_assigned')
                //     ->addViolation();
                // }
                break;
            case State::UnbindReservation:
                // if (null !== $asset->getReservedBy()) {
                //     $this->context->buildViolation('asset.state.unbind_reservation.still_reserved')
                //     ->addViolation();
                // }
                // // check original state
                // if (null === $original['reservedBy']) {
                //     $this->context->buildViolation('asset.state.unbind_reservation.not_reserved')
                //     ->addViolation();
                // }
                break;
            case State::Used:
                break;
            case State::Edited:
                break;
            case State::SavedImage:
                if (!$asset->isHddImageSource()) {
                    $this->context->buildViolation('asset.state.saved_image.invalid_source')
                    ->addViolation();
                }
                break;

            default:
                // code...
                break;
        }
    }
}
