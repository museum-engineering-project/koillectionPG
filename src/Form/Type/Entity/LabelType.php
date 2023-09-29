<?php

declare(strict_types=1);

namespace App\Form\Type\Entity;

use App\Entity\Label;
use Symfony\Component\Form\AbstractType;
use App\Enum\LabelSizeEnum;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LabelType extends AbstractType 
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('labelSize', ChoiceType::class, [
                'choices' => array_flip(LabelSizeEnum::getLabelSizes()),
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Label::class,
        ]);
    }
}