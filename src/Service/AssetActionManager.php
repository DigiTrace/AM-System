<?php

namespace App\Service;

use App\Action\Asset\ActionInterface;
use App\Entity\Asset;
use App\Entity\AssetHistory;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Service for performing asset state transtitions.
 *
 * @author Ben Brooksnieder
 */
class AssetActionManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private TokenStorageInterface $security,
    ) {
    }

    public function isValidAction(Collection $assets, ActionInterface $action): array
    {
        $violations = [];

        foreach ($assets as $asset) {
            $errors = $action->preValidation($this->validator, $asset);

            // if errors exists, append to violations
            if ($errors->count() > 0) {
                $violations[$asset->getBarcode()] = $errors;
            }
        }

        return $violations;
    }

    /**
     * Summary of performAction.
     *
     * @param Collection<Asset> $assets
     *
     * @return void
     */
    public function performAction(Collection $assets, array $data, ActionInterface $action): array
    {
        $data['user'] = $this->security->getToken()?->getUser();

        if (null === $data['user']) {
            return [];
        }

        $violations = [];

        foreach ($assets as $asset) {
            
            // iterate over all compound actions
            $toPersists = [];
            $skip = false;

            foreach ($action->getActions() as $single) {
                // create history and apply changes
                $history = AssetHistory::fromAsset($asset);
                $result = $single->action($asset, $data);

                // if result is null, skip action
                if ($result === null) {
                    continue;
                }

                // set system action to true (unset if necessary later)
                $asset->setSystemAction(true);

                // if result is set with violations, skip asset
                if (!empty($result)) {
                    $violations[$asset->getBarcode()] = new ConstraintViolationList($result);
                    $skip = true;
                    break;
                }

                // finally validate modified asset
                $errors = $this->validator->validate($asset, $single->getConstraints());
                if ($errors->count() > 0) {
                    $violations[$asset->getBarcode()] = $errors;
                    $skip = true;
                    break;
                }

                $toPersists[] = $history;
            }

            if ($skip) {
                continue;
            }
             
            // persist all history and assets
            foreach($toPersists as $history) {
                $this->entityManager->persist($history);
            }
            // last action is not system action
            $asset->setSystemAction(false);
            $this->entityManager->persist($asset);

            if ($asset->isDrive()) {
                $this->entityManager->persist($asset->getDrive());
            }
        }

        if (!empty($violations)) {
            return $violations;
        }

        // apply changes
        $this->entityManager->flush();

        return [];
    }
}
