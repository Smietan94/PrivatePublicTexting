<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class HomeController extends AbstractController
{
    public function __construct(
        private Security $security,
        private UserRepository $userRepository,
    ) {
    }

    #[Route(['/', '/home'], name: 'app_home')]
    // #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        $user    = $this->security->getUser();
        $friends = $this->userRepository->getAllFriends($user);

        return $this->render('home/index.html.twig', [
            'friends' => $friends,
        ]);
    }
}
