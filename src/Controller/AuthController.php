<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Enum\UserStatus;
use App\Form\LoginFormType;
use App\Form\RegisterFormType;
use App\Repository\UserRepository;
use Error;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * AuthController
 */
class AuthController extends AbstractController
{
    private User $currentUser;

    public function __construct(
        private UserRepository $userRepository,
        private FormFactoryInterface $formFactory,
        private ValidatorInterface $validator,
        private Security $security,
        private LoggerInterface $logger,
    ) {
        // collecting logged user if logged in
        if ($this->security->isGranted('ROLE_USER')) {
            $username          = $this->security->getUser()->getUserIdentifier();
            $this->currentUser = $this->userRepository->findOneBy(['username' => $username]);
        }
    }

    /**
     * login
     *
     * @param  AutheticationUtils $authenticationUtils
     * @return Response
     */
    #[Route('/login', name: 'app_login')]
    #[IsGranted('PUBLIC_ACCESS')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        try {
            if ($this->security->isGranted('ROLE_USER')) {
                $this->userRepository->changeStatus(UserStatus::ACTIVE->toInt(), $this->currentUser);
                return $this->redirectToRoute('app_home');
            }

            $form  = $this->formFactory->create(LoginFormType::class);
            $error = $authenticationUtils->getLastAuthenticationError();

            if ($error) {
                $this->addFlash('warning', $error->getMessage());

                return $this->redirectToRoute('app_login');
            }

            return $this->render('auth/login.html.twig', [
                'controller_name' => 'AuthController',
                'loginForm' => $form->createView()
            ]);

        } catch (Error $e) {
            $this->logger->error('Error has occured during login: ' . $e->getMessage());
            $this->addFlash('error', 'Error has occured during login');

            return $this->redirectToRoute('app_register');

        } catch (ValidationFailedException $e) {
            $this->logger->error('Error has occured during login: ' . $e->getMessage());
            $this->addFlash('error', 'Error has occured during login');

            return $this->redirectToRoute('app_register');
        }
    }

    /**
     * register
     *
     * @param  Request $request
     * @return Response
     */
    #[Route('/register', name: 'app_register')]
    #[IsGranted('PUBLIC_ACCESS')]
    public function register(Request $request): Response
    {
        if ($this->security->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('app_home');
        }

        $form = $this->formFactory->create(RegisterFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            return $this->processRegisterForm($form);
        }

        return $this->render('auth/register.html.twig', [
            'registerForm' => $form->createView()
        ]);
    }

    /**
     * logout
     *
     * @return Response
     */
    #[Route('/logout', name: 'app_logout')]
    public function logout(): Response
    {
        return $this->redirectToRoute('app_login');
    }

    /**
     * processRegisterForm
     *
     * @param  FormInterface $form
     * @return Response
     */
    private function processRegisterForm(FormInterface $form): Response
    {
        try {
            if ($form->isValid()) {
                $formData = $form->getData();
                $password = $formData['password'];
                $confirmPassword = $formData['confirm_password'];

                if ($confirmPassword !== $password) {
                    $this->addFlash('warning', 'Passwords are not the same!');
                    return $this->redirectToRoute('app_register');
                }

                // creating new user in database
                $newUser = $this->userRepository->store($formData);
                // login user automatically after adding to db
                $this->addFlash('success', 'User Registration Succeded');
                $this->security->login($newUser, null, 'registration');

                return $this->redirectToRoute('app_home');
            } else {
                foreach($this->validator->validate($form) as $error) {
                    $this->addFlash('warning', $error->getMessage());
                }

                return $this->redirectToRoute('app_register');
            }

        } catch (Error $e) {
            $this->logger->error('Error has occured during registration: ' . $e->getMessage());
            $this->addFlash('error', 'Error has occured during registration');

            return $this->redirectToRoute('app_register');

        } catch (Exception $e) {
            $this->logger->error('Error has occured during registration: ' . $e->getMessage());
            $this->addFlash('error', 'Error has occured during registration');

            return $this->redirectToRoute('app_register');
        }
    }
}