<?php

declare(strict_types=1);

namespace App\Form\Type\Entity;

use App\Entity\Label;
use App\Entity\Item;
use Symfony\Component\Form\AbstractType;
use App\Enum\LabelSizeEnum;
use App\Enum\OrientationEnum;
use App\Enum\TextAlignmentEnum;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
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
            ->add('orientation', ChoiceType::class, [
                'choices' => array_flip(OrientationEnum::getOrientations()),
                'required' => true,
            ])
            ->add('fontSize', IntegerType::class, [
                'required' => true
            ])
            ->add('qrSize', IntegerType::class, [
                'required' => true
            ])
            ->add('textAlignment', ChoiceType::class, [
                'choices' => array_flip(TextAlignmentEnum::getTextAlignments()),
                'required' => true,
            ])
        ;

        $objectsMap = [];
        $uniqueFieldsMap = [];
        $uniqueFields = [];
        // even if we generate label for a single item/collection, it is passed in the array
        foreach ($options["objects"] as $object)
        {
            if ($object instanceof Item)
            {
                $objectsMap[$object->getName()] = $object;
            }
            else // instanceof Collection
            {
                $objectsMap[$object->getTitle()] = $object;
            }

            foreach($object->getPublicDataTexts() as $datum)
            {
                if (!array_key_exists(strtolower($datum->getLabel()), $uniqueFieldsMap))
                {
                    $uniqueFieldsMap[strtolower($datum->getLabel())] = $datum->getLabel();
                    $uniqueFields[$datum->getLabel()] = $datum->getLabel();
                }
            }
        }
        
        if ($options['multiple_items'])
        {
            $builder->add('selectAllItems', CheckboxType::class, [
                'mapped' => false,
                'required' => false,
                'data' => true
            ]);

            $builder->add('object', ChoiceType::class, [
                'choices' => $objectsMap,
                'label' => false,
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'data' => array_values($objectsMap)
            ]);
        }

        $builder->add('selectAllFields', CheckboxType::class, [
            'mapped' => false,
            'required' => false,
            'data' => true
        ]);

        $builder->add('fields', ChoiceType::class, [
            'choices' => $uniqueFields,
            'label' => false,
            'required' => false,
            'multiple' => true,
            'expanded' => true,
            'data' => array_values($uniqueFields)
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Label::class,
            'objects' => [],
            'multiple_items' => false
        ]);
    }
}
