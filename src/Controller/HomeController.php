<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    private User $currentUser;

    public function __construct(
        private Security $security,
        private UserRepository $userRepository,
    ) {
        // collecting logged user
        $username          = $this->security->getUser()->getUserIdentifier();
        $this->currentUser = $this->userRepository->findOneBy(['username' => $username]);
    }

    #[Route(['/', '/home'], name: 'app_home')]
    public function index(): Response
    {
        // collecting all user friends
        $friends = $this->currentUser->getFriends()->toArray();

        return $this->render('home/index.html.twig', [
            'friends' => $friends,
        ]);
    }
}
