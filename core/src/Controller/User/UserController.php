<?php

namespace App\Controller\User;

use App\Command\CommandDispatcherTrait;
use App\Command\Email\SendUserApprovedEmailCommand;
use App\Command\User\ActivateUserCommand;
use App\Command\User\AddUserCommand;
use App\Command\User\DeleteUserCommand;
use App\Command\User\SuspendUserCommand;
use App\Command\User\UpdateUserCommand;
use App\Entity\User\User;
use App\Form\Type\UserType;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * User controller.
 *
 * @Route("/admin/user")
 * @Security("is_granted('manage', 'users')")
 */
class UserController extends AbstractController
{
    use CommandDispatcherTrait;

    public function __construct(
        private AuthorizationCheckerInterface $authChecker,
        private UserPasswordHasherInterface $passwordEncoder,
        private ManagerRegistry $managerRegistry,
    ) {
    }

    /**
     * Lists all user entities.
     *
     * @Route("/", methods={"GET"}, name="admin_user_index")
     */
    public function index(): Response
    {
        $em = $this->managerRegistry->getManager();

        if ($this->authChecker->isGranted('manage', 'all_users')) {
            $users = $em->getRepository(User::class)->findAll();
        } else {
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw new \UnexpectedValueException('Invalid user.');
            }

            $users = $em->getRepository(User::class)
                ->findByOrg($user->getOrg());
        }

        $suspendForm = [];
        $activateForm = [];
        $rejectForm = [];
        /** @var User $user */
        foreach ($users as $user) {
            $suspendForm[$user->getId()] = $this->createSuspendForm($user)->createView();
            $activateForm[$user->getId()] = $this->createActivateForm($user)->createView();
            $rejectForm[$user->getId()] = $this->createRejectForm($user)->createView();
        }

        return $this->render('user/user/index.html.twig', [
            'users' => $users,
            'suspend_form' => $suspendForm,
            'activate_form' => $activateForm,
            'reject_form' => $rejectForm,
        ]);
    }

    /**
     * Creates a new user entity.
     *
     * @Route("/new", methods={"GET", "POST"}, name="admin_user_new")
     */
    public function create(Request $request): Response
    {
        $targetUser = new User();
        $form = $this->createForm(UserType::class, $targetUser, ['validation_groups' => ['registration']]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Set to organization to match the creating users, unless the super-user
            if (!$this->authChecker->isGranted('manage', 'all_users')) {
                $user = $this->getUser();
                if (!$user instanceof User) {
                    throw new \UnexpectedValueException('Invalid user.');
                }

                $targetUser->setOrg($user->getOrg());
            }

            // Encode the plaintext password
            $encryptedPassword = $this->passwordEncoder
                ->hashPassword($targetUser, $targetUser->getPlainPassword());

            try {
                $command = new AddUserCommand($targetUser, $encryptedPassword);
                $this->sendCommand($command);

                return $this->redirectToRoute('admin_user_index');
            } catch (\Exception $e) {
                $form->addError(new FormError($e->getMessage()));
            }
        }

        return $this->render('user/user/new.html.twig', [
            'user' => $targetUser,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a user entity.
     *
     * @Route("/{id}", methods={"GET"}, name="admin_user_show")
     * @Security("is_granted('manage', targetUser)")
     */
    public function show(User $targetUser): Response
    {
        $deleteForm = $this->createDeleteForm($targetUser);

        return $this->render('user/user/show.html.twig', [
            'user' => $targetUser,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing user entity.
     *
     * @Route("/{id}/edit", methods={"GET", "POST"}, name="admin_user_edit")
     * @Security("is_granted('manage', targetUser)")
     */
    public function edit(Request $request, User $targetUser): Response
    {
        $deleteForm = $this->createDeleteForm($targetUser);
        $editForm = $this->createForm(UserType::class, $targetUser);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $plainPassword = $targetUser->getPlainPassword();
            if (!empty($plainPassword)) {
                $password = $this->passwordEncoder
                    ->hashPassword($targetUser, $targetUser->getPlainPassword());
                $targetUser->setPassword($password);
            }

            try {
                $command = new UpdateUserCommand($targetUser);
                $this->sendCommand($command);

                return $this->redirectToRoute('admin_user_index');
            } catch (\Exception $e) {
                $editForm->addError(new FormError($e->getMessage()));
            }
        }

        return $this->render('user/user/edit.html.twig', [
            'user' => $targetUser,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Suspend a user.
     *
     * @Route("/{id}/suspend", methods={"POST"}, name="admin_user_suspend")
     * @Security("is_granted('manage', targetUser)")
     */
    public function suspend(Request $request, User $targetUser): RedirectResponse
    {
        $form = $this->createSuspendForm($targetUser);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $command = new SuspendUserCommand($targetUser);
            $this->sendCommand($command);
        }

        return $this->redirectToRoute('admin_user_index');
    }

    /**
     * Activate a user.
     *
     * @Route("/{id}/activate", methods={"POST"}, name="admin_user_activate")
     * @Security("is_granted('manage', targetUser)")
     */
    public function activate(Request $request, User $targetUser): RedirectResponse
    {
        $form = $this->createActivateForm($targetUser);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $command = new ActivateUserCommand($targetUser);
            $this->sendCommand($command);
        }

        // Send email after user has been approved
        try {
            $command = new SendUserApprovedEmailCommand($targetUser->getUserIdentifier());
            $this->sendCommand($command);
        } catch (\Swift_RfcComplianceException $e) {
            throw new \RuntimeException('A valid email address must be given.');
        } catch (\Exception $e) {
            // Do not throw an error to the client if the email could not be sent
        }

        return $this->redirectToRoute('admin_user_index');
    }

    /**
     * Reject a user.
     *
     * @Route("/{id}/reject", methods={"POST"}, name="admin_user_reject")
     * @Security("is_granted('manage', targetUser)")
     */
    public function reject(Request $request, User $targetUser): RedirectResponse
    {
        $form = $this->createRejectForm($targetUser);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $command = new SuspendUserCommand($targetUser);
            $this->sendCommand($command);
        }

        return $this->redirectToRoute('admin_user_index');
    }

    /**
     * Deletes a user entity.
     *
     * @Route("/{id}", methods={"DELETE"}, name="admin_user_delete")
     * @Security("is_granted('manage', targetUser)")
     */
    public function delete(Request $request, User $targetUser): RedirectResponse
    {
        $form = $this->createDeleteForm($targetUser);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $command = new DeleteUserCommand($targetUser);
            $this->sendCommand($command);
        }

        return $this->redirectToRoute('admin_user_index');
    }

    /**
     * Creates a form to suspend a user entity.
     */
    private function createSuspendForm(User $targetUser): FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_user_suspend', ['id' => $targetUser->getId()]))
            ->setMethod('POST')
            ->getForm();
    }

    /**
     * Creates a form to activate a user entity.
     */
    private function createActivateForm(User $targetUser): FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_user_activate', ['id' => $targetUser->getId()]))
            ->setMethod('POST')
            ->getForm();
    }

    /**
     * Creates a form to reject a user entity.
     */
    private function createRejectForm(User $targetUser): FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_user_reject', ['id' => $targetUser->getId()]))
            ->setMethod('POST')
            ->getForm();
    }

    /**
     * Creates a form to delete a user entity.
     */
    private function createDeleteForm(User $targetUser): FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_user_delete', ['id' => $targetUser->getId()]))
            ->setMethod('DELETE')
            ->getForm();
    }
}
