<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\FriendsService;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FriendController extends AbstractController
{
    public function __construct(
        private Security $security,
        private UserRepository $userRepository,
        private FriendsService $friendsService,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/friends', name: 'app_friends_list')]
    public function index(Request $request): Response
    {
        $username     = $this->security->getUser()->getUserIdentifier();
        $currentUser  = $this->userRepository->findOneBy(['username' => $username]);
        $queryBuilder = $this->userRepository->getFriendsQuery($currentUser);
        $adapter      = new QueryAdapter($queryBuilder);
        $pagerfanta   = Pagerfanta::createForCurrentPageWithMaxPerPage(
            $adapter,
            (int) $request->query->get('page', 1),
            12
        );

        return $this->render('friend/index.html.twig', [
            'pager'        => $pagerfanta,
            'friendsSince' => $this->friendsService->getHowLongFriends($currentUser),
        ]);
    }

    #[Route('/friends/remove', name: 'app_friends_remove')]
    public function removeFriend(Request $request): Response
    {
        $username = $this->security->getUser()->getUserIdentifier();
        $friendId = $request->request->get('friendId');
        $this->friendsService->removeFriend($username, $friendId);

        return $this->redirectToRoute('app_friends_list');
    }

    // #[Route('/deleteUser')]
    // public function del(): Response
    // {
    //     $user = $this->userRepository->findOneBy(['username' => $this->security->getUser()->getUserIdentifier()]);

    //     $this->entityManager->remove($user);
    //     $this->entityManager->flush();

    //     $this->security->logout(false);

    //     return $this->redirectToRoute('app_home');
    // }
}
