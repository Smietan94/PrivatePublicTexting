<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Constants\RouteName;
use App\Entity\Constants\RoutePath;
use App\Entity\User;
use App\Exception\MethodDoesNotExistException;
use App\Form\ChangeEmailType;
use App\Form\ChangePasswordType;
use App\Form\ChangeUsernameType;
use App\Form\DeleteAccountType;
use App\Repository\UserRepository;
use App\Service\SettingsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SettingsController extends AbstractController
{
    private User $currentUser;

    public function __construct(
        private ValidatorInterface $validator,
        private Security           $security,
        private SettingsService    $settingsService,
        private UserRepository     $userRepository
    ) {
        // collecting logged user
        $username          = $this->security->getUser()->getUserIdentifier();
        $this->currentUser = $this->userRepository->findOneBy(['username' => $username]);
    }

    /**
     * handleChangeEmail
     *
     * @param  Request $request
     * @return Response
     */
    #[Route(
        RoutePath::SETTINGS_CHANGE_EMAIL,
        name: RouteName::APP_SETTINGS_CHANGE_EMAIL
    )]
    public function handleEmailChange(Request $request): Response
    {
        $form = $this->settingsService->createSettingsForm(
            ChangeEmailType::class,
            $this->generateUrl(RouteName::APP_SETTINGS_CHANGE_EMAIL)
        );

        return $this->handleCredentialsUpdate(
            $request,
            $form,
            '_changeEmailFormModal.html.twig',
            'updateEmail'
        );
    }

    /**
     * handleUsernameChange
     *
     * @param  Request $request
     * @return Response
     */
    #[Route(
        RoutePath::SETTINGS_CHANGE_USERNAME,
        name: RouteName::APP_SETTINGS_CHANGE_USERNAME
    )]
    public function handleUsernameChange(Request $request): Response
    {
        $form = $this->settingsService->createSettingsForm(
            ChangeUsernameType::class,
            $this->generateUrl(RouteName::APP_SETTINGS_CHANGE_USERNAME)
        );

        return $this->handleCredentialsUpdate(
            $request,
            $form,
            '_changeUsernameFormModal.html.twig',
            'updateUsername'
        );
    }

    /**
     * changePassword
     *
     * @param  Request $request
     * @return Response
     */
    #[Route(
        RoutePath::SETTINGS_CHANGE_PASSWORD,
        name: RouteName::APP_SETTINGS_CHANGE_PASSWORD
    )]
    public function changePassword(Request $request): Response
    {
        $form = $this->settingsService->createSettingsForm(
            ChangePasswordType::class,
            $this->generateUrl(RouteName::APP_SETTINGS_CHANGE_PASSWORD)
        );

        return $this->handleCredentialsUpdate(
            $request,
            $form,
            '_changePasswordFormModal.html.twig',
            'updatePassword'
        );
    }

    /**
     * deleteAccount
     *
     * @param  Request $request
     * @return Response
     */
    #[Route(
        RoutePath::SETTINGS_DELETE_ACCOUNT,
        name: RouteName::APP_SETTINGS_DELETE_ACCOUNT
    )]
    public function deleteAccount(Request $request): Response
    {
        $form = $this->settingsService->createSettingsForm(
            DeleteAccountType::class,
            $this->generateUrl(RouteName::APP_SETTINGS_DELETE_ACCOUNT)
        );

        return $this->handleCredentialsUpdate(
            $request,
            $form,
            '_deleteAccountFormModal.html.twig',
            'processUserSoftDelete'
        );
    }

    /**
     * handleCredentialsUpdate
     *
     * @param  Request       $request
     * @param  FormInterface $form
     * @param  string        $filename
     * @param  string        $method
     * @return Response
     */
    private function handleCredentialsUpdate(Request $request, FormInterface $form, string $filename, string $method): ?Response
    {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            if (isset($data['new_password']) && $data['new_password'] !== $data['confirm_password']) {
                $this->addFlash('warning', 'Passwords are not the same!');

                return $this->redirectToRoute(RouteName::APP_HOME);
            }

            if (isset($data['user_id']) && !($this->currentUser->getId() === (int) $data['user_id'])) {
                $this->addFlash('warning', 'Invalid user data');

                return $this->redirectToRoute(RouteName::APP_HOME);
            }

            // check if method exists if not throw exception
            if (!method_exists(SettingsService::class, $method)) {
                throw new MethodDoesNotExistException(sprintf('Method %s does not exist!', $method));
            }

            $this->settingsService->$method($this->currentUser, $data);

            return $this->redirectToRoute(RouteName::APP_HOME);

        } else if ($form->isSubmitted() && !$form->isValid()) {
            foreach($this->validator->validate($form) as $error) {
                $this->addFlash('warning', $error->getMessage());
            }

            return $this->redirectToRoute(RouteName::APP_HOME);
        }

        return $this->render(sprintf('nav_dropdown/settings/%s', $filename), [
            'form' => $form,
        ]);
    }
}