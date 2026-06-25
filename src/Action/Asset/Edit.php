<?php

namespace App\Action\Asset;

use App\Entity\Asset;
use App\Enum\AssetState as State;
use App\Form\Asset\DriveType;
use Symfony\Component\Form\Event\PostSetDataEvent;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type as Field;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints;

/**
 * @author Ben Brooksnieder
 */
class Edit extends AssetAction
{
    protected string $name = 'asset.actions.edit';
    protected bool $isSystemAction = false;
    protected bool $confirmationRequired = true;
    protected bool $usageRequired = true;
    protected ?State $newState = State::Edited;
    protected array $messages = [
        ['info', 'asset.edit.info'], #TODO revise
    ];

    protected function action(Asset $asset, $data): array
    {
        $asset->setName($data['name']);
        $asset->setNote($data['note']);

        return [];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', Field\TextType::class, [
                'label' => 'asset.edit.form.name',
                'constraints' => new Constraints\NotBlank(),
                'required' => true,
            ])
            ->add('note', Field\TextareaType::class, [
                'label' => 'asset.edit.form.note',
                'required' => false,
            ])
        ;

        // add listener to add drive form depending on category
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (PostSetDataEvent $event): void {
            $data = $event->getData();
            $form = $event->getForm();

            // add drive form if asset is drive
            if ($data['drive']) {
                 $form->add('drive', DriveType::class);
            }
        });
    }

    public function getConstraints(): array
    {
        return [new \App\Validator\Asset\Edit()];
    }
}
