<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Constants\RouteName;
use App\Entity\Constants\RoutePath;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\FriendsService;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Adapter\ArrayAdapter;
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
        private Security               $security,
        private UserRepository         $userRepository,
        private FriendsService         $friendsService,
        private EntityManagerInterface $entityManager
    ) {
        // collecting logged user
        $username          = $this->security->getUser()->getUserIdentifier();
        $this->currentUser = $this->userRepository->findOneBy(['username' => $username]);
    }

    /**
     * index
     *
     * @param  Request $request
     * @return Response
     */
    #[Route(
        RoutePath::FRIENDS,
        name: RouteName::APP_FRIENDS_LIST
    )]
    public function index(Request $request): Response
    {
        return $this->processFriendsList($request, 'friend/index.html.twig');
    }

    /**
     * friendListReload
     *
     * @param  Request $request
     * @return Response
     */
    #[Route(
        RoutePath::RELOAD_FRIENDS_LIST,
        name: RouteName::APP_RELOAD_FRIENDS_LIST
    )]
    public function friendListReload(Request $request): Response
    {
        return $this->processFriendsList($request, 'friend/_friendsList.html.twig');
    }

    /**
     * removeFriend
     *
     * @param  Request $request
     * @return Response
     */
    #[Route(
        RoutePath::REMOVE_FRIEND,
        methods:['DELETE'],
        name: RouteName::APP_FRIENDS_REMOVE
    )]
    public function removeFriend(Request $request): Response
    {
        // collecting frieng to remove
        $friendId = (int) $request->request->get('friendId');
        $friend   = $this->userRepository->find($friendId);

        // check if friend exists
        if (!$friend) {
            $this->addFlash('error', 'User does not exist');
            return $this->redirectToRoute(RouteName::APP_FRIENDS_LIST);
        }

        // check if user in friends list
        if (!in_array($friend, $this->currentUser->getFriends()->toArray())) {
            $this->addFlash('error', 'You are not friends');
            return $this->redirectToRoute(RouteName::APP_FRIENDS_LIST);
        }

        $this->friendsService->removeFriend($this->currentUser, $friend);

        return $this->redirectToRoute(RouteName::APP_FRIENDS_LIST);
    }

    /**
     * processFriendsList
     *
     * @param  Request $request
     * @param  string  $path
     * @return Response
     */
    public function processFriendsList(Request $request, string $path): Response
    {
        $currentUser = $this->currentUser;
        $adapter      = new ArrayAdapter($currentUser->getFriends()->toArray());
        $pagerfanta   = Pagerfanta::createForCurrentPageWithMaxPerPage(
            $adapter,
            (int) $request->query->get('page', 1),
            6
        );

        return $this->render($path, [
            'pager'        => $pagerfanta,
            'friendsSince' => $this->friendsService->getHowLongFriends($currentUser), // collecting date of accepting friend request
        ]);
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
