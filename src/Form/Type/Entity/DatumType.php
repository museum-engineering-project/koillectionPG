<?php

declare(strict_types=1);

namespace App\Form\Type\Entity;

use App\Entity\ChoiceList;
use App\Entity\Datum;
use App\Enum\DatumTypeEnum;
use App\Enum\VisibilityEnum;
use App\Repository\ChoiceListRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DatumType extends AbstractType
{
    public function __construct(
        private readonly Security $security,
        private readonly ChoiceListRepository $choiceListRepository
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', TextType::class, [
                'required' => true,
            ])
            ->add('label', TextType::class, [
                'required' => false,
            ])
            ->add('fileImage', FileType::class, [
                'required' => false,
                'label' => false,
            ])
            ->add('fileFile', FileType::class, [
                'required' => false,
                'label' => false,
            ])
            ->add('position', TextType::class, [
                'required' => false,
            ])
            ->add('visibility', ChoiceType::class, [
                'choices' => array_flip(VisibilityEnum::getVisibilityLabels()),
                'required' => true,
            ])
        ;

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event): void {
                $form = $event->getForm();
                $data = $event->getData();

                match ($data['type']) {
                    DatumTypeEnum::TYPE_RATING => $form
                        ->add('value', ChoiceType::class, [
                            'choices' => array_combine(range(1, 10), range(1, 10)),
                            'expanded' => true,
                            'multiple' => false,
                            'required' => false,
                        ]),
                    DatumTypeEnum::TYPE_CHECKBOX => $form
                        ->add('value', CheckboxType::class, [
                            'required' => false,
                            'model_transformer' => new CallbackTransformer(
                                static function ($value) {
                                    return $value === true ? 'on' : null;
                                },
                                static function ($value) {
                                    return $value === true ? '1' : '0';
                                }
                            ),
                        ]),
                    DatumTypeEnum::TYPE_DATE => $form
                        ->add('value', TextType::class, [
                            'required' => false,
                            'model_transformer' => new CallbackTransformer(
                                function ($string): ?string {
                                    if (empty($string)) {
                                        return null;
                                    }

                                    $date = \DateTimeImmutable::createFromFormat('Y-m-d', $string);
                                    if (!$date) {
                                        return null;
                                    }

                                    return $date->format($this->security->getUser()->getDateFormat()) ?: null;
                                },
                                function ($date): ?string {
                                    if (empty($date)) {
                                        return null;
                                    }

                                    $date = \DateTimeImmutable::createFromFormat($this->security->getUser()->getDateFormat(), $date);
                                    if (!$date) {
                                        return null;
                                    }

                                    return $date->format('Y-m-d') ?: null;
                                }
                            ),
                        ]),
                    DatumTypeEnum::TYPE_LINK => $form
                        ->add('value', UrlType::class, [
                            'required' => false,
                        ]),
                    DatumTypeEnum::TYPE_CHOICE_LIST => $form
                        ->add('value', ChoiceType::class, [
                            'multiple' => true,
                            'required' => false,
                            'choices' => $this->choiceListRepository->find($data['choiceList'])->getChoices(),
                            'model_transformer' => new CallbackTransformer(
                                static function ($string) {
                                    return null !== $string ? json_decode($string, true) : null;
                                },
                                static function ($array) {
                                    return \is_array($array) ? json_encode($array) : null;
                                }
                            ),
                        ])
                        ->add('choiceList', EntityType::class, [
                            'class' => ChoiceList::class,
                            'required' => true,
                        ]),
                    default => $form
                        ->add('value', TextType::class, [
                            'required' => false,
                        ]),
                };
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Datum::class,
        ]);
    }
}
