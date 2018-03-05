<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Form\RegistrationType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Form\UserType;

class UserController extends Controller
{
    /**
     * @Route("/admin/users/{page}", requirements={"page": "\d*"}, defaults={"page": "1"}, name="admin_users_index")
     */
    public function indexAction($page, $nbPerPage = User::NB_PER_PAGE)
    {
        $em = $this->getDoctrine()->getManager();
        $em->getFilters()->disable('userfilter');

        $list = $em
            ->getRepository(User::class)
            ->getUsersPerPage($page, $nbPerPage);

        $nbPages = ceil(count($list) / $nbPerPage);

        if ($page > $nbPages && $page > 1 || $page == 0) {
            throw $this->createNotFoundException($this->get('translator')->trans("controler.page.unknow", array('%page%' => $page)));
        }

        return $this->render('admin/user/index.html.twig', [
            'list' => $list->getIterator(),
            'total' => count($list),
            'nbPages' => $nbPages,
            'page' => $page,
        ]);
    }

    /**
     * @Route("/admin/users/edit/{id}", requirements={"id": "\d+"}, name="admin_users_edit")
     */
    public function editAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $em->getFilters()->disable('userfilter');

        $userManager = $this->get('fos_user.user_manager');
        $user = $userManager->findUserBy(array('id' => $id));

        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $userManager->updateUser($user);

            $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.admin.users.edit.success"), array('%user%' => $user->getFullname()));

            return $this->redirectToRoute('admin_users_index');
        }

        return $this->render('admin/user/edit.html.twig', array(
            'form' => $form->createView(),
            '$user' => $user,
        ));
    }

    /**
     * @Route("/admin/users/enable/toggle/{id}", requirements={"id": "\d+"}, name="admin_users_enable_toggle")
     */
    public function enableToggleAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $em->getFilters()->disable('userfilter');

        $userManager = $this->get('fos_user.user_manager');
        $user = $userManager->findUserBy(array('id' => $id));

        if ($user->isEnabled())
        {
            $user->setEnabled(false);
            $request->getSession()->getFlashBag()->add('warning', $this->get('translator')->trans("flash.admin.users.disable.toggle.success"), array('%user%' => $user->getFullname()));
        }
        else
        {
            $user->setEnabled(true);
            $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.admin.users.enable.toggle.success"), array('%user%' => $user->getFullname()));
        }

        $userManager->updateUser($user);

        return $this->redirectToRoute('admin_users_index');
    }

    /**
     * @Route("/admin/users/new", name="admin_users_new")
     */
    public function newAction(Request $request)
    {
        $user = new User();

        $form = $this->createForm(RegistrationType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em = $this->getDoctrine()->getManager();
            $em->getFilters()->disable('userfilter');

            $userManager = $this->get('fos_user.user_manager');

            $emailExists = $userManager->findUserByEmail($user->getEmail());
            $usernameExist = $userManager->findUserByUsername($user->getUsername());

            if(!$emailExists instanceof User && !$usernameExist instanceof User)
            {
                $user->setEnabled(true);
                $userManager->updateUser($user);

                $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash..new.success"), array('%email%' => $user->getEmail()));

                return $this->redirectToRoute('admin_users_index');
            }

            if ($emailExists instanceof User) {
                $form->get('email')->addError(new FormError($this->get('translator')->trans("flash.admin.users.create.email_exist")));
            }

            if ($usernameExist instanceof User) {
                $form->get('username')->addError(new FormError($this->get('translator')->trans("flash.admin.users.create.email_exist")));
            }
        }

        return $this->render('admin/user/add.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/admin/users/auths/{auth}/toggle/{id}", requirements={"id": "\d+"}, name="admin_users_auths_toggle")
     */
    public function authToggleAction($auth, $id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $em->getFilters()->disable('userfilter');

        $userManager = $this->get('fos_user.user_manager');
        $user = $userManager->findUserBy(array('id' => $id));

        switch ($auth) {
            case "crm":
                if ($user->hasRole('ROLE_CRM'))
                {
                    $user->removeRole('ROLE_CRM');
                    $request->getSession()->getFlashBag()->add('warning', $this->get('translator')->trans("flash.admin.users.disallow.toggle.success"), array('%user%' => $user->getFullname(), '%auth%' => $auth));
                }
                else
                {
                    $user->addRole('ROLE_CRM');
                    $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.admin.users.allow.toggle.success"), array('%user%' => $user->getFullname(), '%auth%' => $auth));
                }
                break;
            case "srm":
                if ($user->hasRole('ROLE_SRM'))
                {
                    $user->removeRole('ROLE_SRM');
                    $request->getSession()->getFlashBag()->add('warning', $this->get('translator')->trans("flash.admin.users.disallow.toggle.success"), array('%user%' => $user->getFullname(), '%auth%' => $auth));
                }
                else
                {
                    $user->addRole('ROLE_SRM');
                    $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.admin.users.allow.toggle.success"), array('%user%' => $user->getFullname(), '%auth%' => $auth));
                }
                break;
            case "bom":
                if ($user->hasRole('ROLE_BOM'))
                {
                    $user->removeRole('ROLE_BOM');
                    $request->getSession()->getFlashBag()->add('warning', $this->get('translator')->trans("flash.admin.users.disallow.toggle.success"), array('%user%' => $user->getFullname(), '%auth%' => $auth));
                }
                else
                {
                    $user->addRole('ROLE_BOM');
                    $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.admin.users.allow.toggle.success"), array('%user%' => $user->getFullname(), '%auth%' => $auth));
                }
                break;
        }

        $userManager->updateUser($user);

        return $this->redirectToRoute('admin_users_index');
    }

}
