<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\SearchFormType;
use App\Repository\FriendRequestRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
    public function __construct(
        private Security $security,
        private UserRepository $userRepository,
        private FormFactoryInterface $formFactory,
        private FriendRequestRepository $friendRequestRepository,
    ) {
    }

    #[Route('/search', methods: ['GET'], name: 'app_search_users')]
    public function index(Request $request): Response
    {
        $user       = $this->security->getUser();
        $username   = $user->getUserIdentifier();
        $searchTerm = $request->query->get('q');

        if ($request->query->get('preview')) {
            return $this->processSearch($searchTerm, $username);
        }

        $form = $this->formFactory->create(SearchFormType::class);
        return $this->render('search/searchUsers.html.twig', [
            'searchForm' => $form->createView(),
        ]);
    }

    private function processSearch(string $searchTerm, string $username): Response
    {
        if ($searchTerm) {
            $users            = $this->userRepository->findUsers($searchTerm, $username);
            $currentUser      = $this->userRepository->findOneBy(['username' => $username]);
            $sentRequests     = $currentUser->getSentFriendRequests()->toArray();
            $receivedRequests = $currentUser->getReceivedFriendRequests()->toArray();
            $friends          = $currentUser->getFriends()->toArray();

            // getting list of already invated users from sentRequests
            $alreadyRequested = array_map(
                fn($friendRequest) => $friendRequest->getRequestedUser(),
                $sentRequests
            );

            $alreadyReceived = array_map(
                fn($friendRequest) => $friendRequest->getRequestingUser(),
                $receivedRequests,
            );
        }

        return $this->render('search/_searchPreview.html.twig', [
            'users'            => $users ?? null,
            'friends'          => $friends ?? null,
            'alreadyRequested' => $alreadyRequested ?? null,
            'alreadyReceived'  => $alreadyReceived ?? null,
        ]);
    }
}
