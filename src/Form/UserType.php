<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('login', null, [
                'label' => 'Логин',
                'attr' => ['class' => 'form-control'],
                'required' => true
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'label' => 'Пароль',
                'first_options' => [
                    'label' => ' ',
                    'attr' => [
                        'class' => 'form-control',
                        'style' => 'margin-bottom: 10px',
                        'placeholder' => 'Введите пароль'
                    ]
                ],
                'second_options' => [
                    'label' => ' ',
                    'attr' => ['class' => 'form-control', 'placeholder' => 'Повторите пароль']
                ],
                'invalid_message' => 'Пароли не совпадают',
                'required' => true
            ])
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
