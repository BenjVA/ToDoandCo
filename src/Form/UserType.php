<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, ['label' => "Nom d'utilisateur"])
            ->add('password', RepeatedType::class, [
                'type'            => PasswordType::class,
                'invalid_message' => 'Les deux mots de passe doivent correspondre.',
                'required'        => true,
                'first_options'   => ['label' => 'Mot de passe'],
                'second_options'  => ['label' => 'Tapez le mot de passe à nouveau'],
            ])
            ->add('email', EmailType::class, ['label' => 'Adresse email'])
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'Utilisateur'    => 'ROLE_USER',
                    'Administrateur' => 'ROLE_ADMIN'
                ],
                'label'   => 'Rôle'
            ])
            ->get('roles')->addModelTransformer(new CallbackTransformer(
                function ($rolesArray) {
                    if (in_array('ROLE_ADMIN', $rolesArray, true)) {
                        return 'ROLE_ADMIN';
                    }
                    return 'ROLE_USER';
                },
                function ($rolesString) {
                    return [$rolesString];
                }
            ));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
