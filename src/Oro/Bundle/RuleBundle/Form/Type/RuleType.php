<?php

namespace Oro\Bundle\RuleBundle\Form\Type;

use Oro\Bundle\RuleBundle\Entity\Rule;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RuleType extends AbstractType
{
    const BLOCK_PREFIX = 'oro_rule';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextareaType::class, [
                'label' => 'oro.rule.rule.name.label'
            ])
            ->add('enabled', CheckboxType::class, [
                'label' => 'oro.rule.rule.enabled.label'
            ])
            ->add('sortOrder', NumberType::class, [
                'label' => 'oro.rule.rule.sort_order.label'
            ])
            ->add('stopProcessing', CheckboxType::class, [
                'required' => false,
                'label' => 'oro.rule.rule.stop_processing.label',
            ])
            ->add('expression', TextareaType::class, [
                'label' => 'oro.rule.rule.expression.label'
            ])
        ;
    }

    /**
     * @param OptionsResolver $resolver
     * @throws AccessException
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Rule::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::BLOCK_PREFIX;
    }
}
