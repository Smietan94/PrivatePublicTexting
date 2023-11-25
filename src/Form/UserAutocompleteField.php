<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

#[AsEntityAutocompleteField]
class UserAutocompleteField extends AbstractType
{
    public function __construct(
        private Security $security,
        private UserRepository $userRepository
    ) {
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        // collecting current user
        $username    = $this->security->getUser()->getUserIdentifier();
        $currentUser = $this->userRepository->findOneBy(['username' => $username]);

        $resolver->setDefaults([
            'class'         => User::class,
            'choice_label'  => 'username',
            'choice_value'  => 'id',
            'multiple'      => true,
            'query_builder' => function (UserRepository $er) use($username, $currentUser): QueryBuilder {
                $qb = $er->createQueryBuilder('u');

                // collecting current user friends
                return $qb
                    ->select('partial u.{id, username}')
                    ->orderBy('u.username', 'ASC')
                    ->andWhere('u.username != :username')
                    ->andWhere(
                        $qb->expr()->isMemberOf(':user', 'u.friends')
                    )
                    ->setParameters([
                        'username' => $username,
                        'user'     => $currentUser
                    ]);
            },
            'filter_query' => function(QueryBuilder $qB, string $query) use($username, $currentUser) {
                if (!$query) {
                    return;
                }

                $qB
                    ->select('partial u.{id, username}')
                    ->andWhere(
                        $qB->expr()->orX(
                            $qB->expr()->like('LOWER(u.username)', ':searchTerm'),
                            $qB->expr()->like('LOWER(u.email)', ':searchTerm')
                    ))
                    ->andWhere(
                        $qB->expr()->isMemberOf(':user', 'u.friends')
                    )
                    ->andWhere('u.username != :username')
                    ->setParameters([
                        'searchTerm' => '%' . strtolower($query) . '%',
                        'username'   => $username,
                        'user'       => $currentUser
                    ]);
            },
            'attr' => [
                'class'       => 'form-control-lg',
                'placeholder' => 'Choose a friend',
            ]
            // 'security' => 'ROLE_SOMETHING',
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
