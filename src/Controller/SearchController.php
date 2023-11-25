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
    private User $currentUser;

    public function __construct(
        private Security $security,
        private UserRepository $userRepository,
        private FormFactoryInterface $formFactory,
        private FriendRequestRepository $friendRequestRepository,
    ) {
        // collecting logged in user
        $username          = $this->security->getUser()->getUserIdentifier();
        $this->currentUser = $this->userRepository->findOneBy(['username' => $username]);
    }

    #[Route('/search', methods: ['GET'], name: 'app_search_users')]
    public function index(Request $request): Response
    {
        $searchTerm = $request->query->get('q');

        // checking if ajax call made
        if ($request->query->get('preview')) {
            return $this->processSearch($searchTerm);
        }

        $form = $this->formFactory->create(SearchFormType::class);
        return $this->render('search/index.html.twig', [
            'searchForm' => $form->createView(),
        ]);
    }

    private function processSearch(string $searchTerm): Response
    {
        // checking if searchterm was sent
        if ($searchTerm) {
            $users = $this->userRepository->findUsers(
                $searchTerm,
                $this->currentUser->getUsername()
            );

            $sentRequests     = $this->currentUser->getSentFriendRequests()->toArray();
            $receivedRequests = $this->currentUser->getReceivedFriendRequests()->toArray();
            $friends          = $this->currentUser->getFriends()->toArray(); // collecting friend list

            // getting list of already invated users from sentRequests
            $alreadyRequested = array_map(
                fn($friendRequest) => $friendRequest->getRequestedUser(),
                $sentRequests
            );

            // collecting list of already received requests
            $alreadyReceived = array_map(
                fn($friendRequest) => $friendRequest->getRequestingUser(),
                $receivedRequests,
            );
        }

        // in case no search term or empty result null is sent back
        return $this->render('search/_searchPreview.html.twig', [
            'users'            => $users ?? null,
            'friends'          => $friends ?? null,
            'alreadyRequested' => $alreadyRequested ?? null,
            'alreadyReceived'  => $alreadyReceived ?? null,
        ]);
    }
}
