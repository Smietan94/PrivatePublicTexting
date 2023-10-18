<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
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
    private User $currentUser;

    public function __construct(
        private Security $security,
        private UserRepository $userRepository,
        private FriendsService $friendsService,
        private EntityManagerInterface $entityManager
    ) {
        // collecting logged user
        $username          = $this->security->getUser()->getUserIdentifier();
        $this->currentUser = $this->userRepository->findOneBy(['username' => $username]);
    }

    #[Route('/friends', name: 'app_friends_list')]
    public function index(Request $request): Response
    {
        // collecting paginated query
        $queryBuilder = $this->userRepository->getFriendsQuery($this->currentUser);
        $adapter      = new QueryAdapter($queryBuilder);
        $pagerfanta   = Pagerfanta::createForCurrentPageWithMaxPerPage(
            $adapter,
            (int) $request->query->get('page', 1),
            6
        );

        return $this->render('friend/index.html.twig', [
            'pager'        => $pagerfanta,
            'friendsSince' => $this->friendsService->getHowLongFriends($this->currentUser), // collecting date of accepting friend request
        ]);
    }

    #[Route('/friends/remove', name: 'app_friends_remove')]
    public function removeFriend(Request $request): Response
    {
        // collecting frieng to remove
        $friendId = (int) $request->request->get('friendId');
        $friend   = $this->userRepository->find($friendId);

        // check if friend exists
        if (!$friend) {
            $this->addFlash('error', 'User does not exist');
            return $this->redirectToRoute('app_friends_list');
        }

        // check if user in friends list
        if (!in_array($friend, $this->currentUser->getFriends()->toArray())) {
            $this->addFlash('error', 'You are not friends');
            return $this->redirectToRoute('app_friends_list');
        }

        $this->friendsService->removeFriend($this->currentUser, $friend);

        return $this->redirectToRoute('app_friends_list');
    }

    // #[Route('/deleteUser')]
    // public function del(): Response
    // {
    //     $this->entityManager->remove($this->currentUser);
    //     $this->entityManager->flush();

    //     $this->security->logout(false);

    //     return $this->redirectToRoute('app_home');
    // }
}
