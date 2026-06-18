<?php

namespace App\Form\Asset;

use App\Entity\CaseFile;
use App\Form\Type\ListType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form for assigning case to assets.
 *
 * @author Ben Brooksnieder
 */
class AssignCaseType extends AbstractType implements DataTransformerInterface
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
        return ListType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'api' => 'asset_action_query_cases',
            'identifier' => 'case',
            'selector_label' => 'asset.action.form.case_search',
            'selector_help' => 'asset.action.form.help.case_search',
        ]);
    }

    /**
     * Transforms a case to a string (id).
     *
     * @param CaseFile|null $case
     */
    public function transform($case): mixed
    {
        if (null === $case) {
            return [];
        }

        return ['item' => $case->getCaseId()];
    }

    /**
     * Transforms a string (case id) to a case.
     *
     * @param array $data
     *
     * @throws TransformationFailedException if case is not found
     */
    public function reverseTransform($data): ?CaseFile
    {
        // no issue number? It's optional, so that's ok
        if (!$data || empty($data['item']) || '0' === $data['item']) {
            return null;
        }

        $case = $this->entityManager
            ->getRepository(CaseFile::class)
            ->findOneBy(['caseId' => $data['item']])
        ;

        if (null === $case) {
            // causes a validation error
            // this message is not shown to the user
            // see the invalid_message option
            throw new TransformationFailedException(sprintf('A case with case id "%s" does not exist!', $data['item']));
        }

        return $case;
    }
}
