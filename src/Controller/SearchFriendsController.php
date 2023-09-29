<?php

namespace App\Controller;

use App\Form\SearchFormType;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class SearchFriendsController extends AbstractController
{
    public function __construct(
        private Security $security,
        private UserRepository $userRepository,
        private FormFactoryInterface $formFactory,
    ) {
    }

    #[Route('/search', methods: ['GET'], name: 'app_search')]
    // #[IsGranted('ROLE_USER')]
    public function index(Request $request): Response
    {
        $user       = $this->security->getUser();
        $searchTerm = $request->query->get('q');

        if ($searchTerm) {
            $users = $this->userRepository->findUsers($searchTerm, $user->getUserIdentifier());
        } else {
            $users = [];
        }

        if ($request->query->get('preview')) {
            return $this->render('search/_searchPreview.html.twig', [
                'users' => $users
            ]);
        }

        $form = $this->formFactory->create(SearchFormType::class);
        return $this->render('search/index.html.twig', [
            'searchForm' => $form->createView(),
        ]);
    }

    // #[Route('/search/{slug}', name: 'app_search_friends')]
    // public function searchFriends(Request $request, string $slug = null): Response
    // {
    //     // if ($request->query->get('preview')) {
    //         return $this->render('search/_searchPreview.html.twig', [
    //             'users' => ['username', 'Kluska Kot', 'ja', 'wy', $request->query->get('q')]
    //         ]);
    //     // }
    // }
}
