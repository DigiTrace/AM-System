<?php

namespace App\Service;

use App\Action\Asset\AssetAction;
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

    public function isValidAction(Collection $assets, AssetAction $action): array
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
    public function performAction(Collection $assets, array $data, AssetAction $action): array
    {
        $data['user'] = $this->security->getToken()?->getUser();

        if (null === $data['user']) {
            return [];
        }

        $violations = [];

        foreach ($assets as $asset) {
            // create history and apply changes
            $history = AssetHistory::fromAsset($asset);
            $res = $action->performAction($asset, $data);

            if (!empty($res)) {
                $violations[$asset->getBarcode()] = new ConstraintViolationList($res);
                continue;
            }

            // finally validate modified asset
            $errors = $this->validator->validate($asset, $action->getFinalConstraints());
            if ($errors->count() > 0) {
                $violations[$asset->getBarcode()] = $errors;
                continue;
            }

            $this->entityManager->persist($asset);
            $this->entityManager->persist($history);

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
