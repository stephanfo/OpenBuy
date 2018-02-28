<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

class ProfileController extends Controller
{
    /**
     * @Route("/profile/api/token", name="profile_api_token_view")
     */
    public function viewAction()
    {
        $user = $this->getUser();

        return $this->render('profile/api/view.html.twig', array(
            'user' => $user,
        ));
    }

    /**
     * @Route("/profile/api/token/new", name="profile_api_token_new")
     */
    public function newAction(Request $request)
    {
        $user = $this->getUser();
        $user->setApiToken(md5(uniqid()));
        $this->get('fos_user.user_manager')->updateUser($user);

        $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.user.api.new"));

        return $this->redirectToRoute('profile_api_token_view');
    }

    /**
     * @Route("/profile/api/token/delete", requirements={"id": "\d+"}, name="profile_api_token_delete")
     */
    public function deleteAction(Request $request)
    {
        $user = $this->getUser();
        $user->setApiToken(null);
        $this->get('fos_user.user_manager')->updateUser($user);

        $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.user.api.delete"));

        return $this->redirectToRoute('profile_api_token_view');
    }

}
