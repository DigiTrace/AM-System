<?php

namespace App\Form\Asset;

use App\Entity\Asset;
use App\Form\Type\AssetListType;
use App\Repository\AssetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form for selecting hdd image sources.
 *
 * @author Ben Brooksnieder
 */
class ImageSourceType extends AbstractType implements DataTransformerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer($this);
    }

    public function getParent(): string
    {
        return AssetListType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'api' => 'asset_action_query_image_sources',
            'identifier' => 'imageSource',
            'validation_groups' => [],
        ]);
    }

    /**
     * Transforms a asset to a string (id).
     *
     * @param Asset|null $asset
     */
    public function transform($asset): mixed
    {
        if (null === $asset) {
            return [];
        }

        return ['item' => $asset->getBarcode()];
    }

    /**
     * Transforms a string (asset id) to a asset.
     *
     * @param array $data
     *
     * @throws TransformationFailedException if asset is not found
     */
    public function reverseTransform($data): ?Asset
    {
        // no issue number? It's optional, so that's ok
        if (!$data || empty($data['item'])) {
            return null;
        }

        /**
         * @var AssetRepository
         */
        $repository = $this->entityManager->getRepository(Asset::class);

        $builder = $repository->createQueryBuilder('a')
        ->where($repository->isHddImageSourceQuery('a'))
        ->andWhere($repository->isEditableQuery('a'))
        ->andWhere('a.barcode = :barcode')
        ->setParameter('barcode', $data['item']);

        $asset = $builder->getQuery()->getOneOrNullResult();

        if (null === $asset) {
            // causes a validation error
            // this message is not shown to the user
            // see the invalid_message option
            throw new TransformationFailedException(sprintf('An asset with barcode "%s" does not exist!', $data['item']));
        }

        return $asset;
    }
}
