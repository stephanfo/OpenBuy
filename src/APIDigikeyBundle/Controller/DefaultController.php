<?php

namespace APIDigikeyBundle\Controller;

use APIDigikeyBundle\Service\InterfaceDigikey;
use AppBundle\Entity\Supplier;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use APIDigikeyBundle\Service\ApiDigikey;

class DefaultController extends Controller
{
    /**
     * @Route("/code", name="api_digikey_code")
     */
    public function codeAction(Request $request, InterfaceDigikey $interfaceDigikey)
    {
        $session = $request->getSession();

        if (!$session->has("interface_digikey_code_id")) {
            return new Response($this->get('translator')->trans("error.bad_id", array(), "interface"), Response::HTTP_BAD_REQUEST);
        }

        $code = $request->query->get('code');

        if (!is_string($code)) {
            return new Response($this->get('translator')->trans("error.bad_code", array(), "interface"), Response::HTTP_BAD_REQUEST);
        }

        $params = $session->get("interface_digikey_code_id");
        $id = $params['id'];
        $redirect = $params['redirect'];
        $session->remove("interface_digikey_code_id");

        $interfaceDigikey->setCodeAndToken($request->headers->get('User-Agent'), $code, $id);

        $session->getFlashBag()->add('success', $this->get('translator')->trans("flash.interface.code.success", array('%supplier%' => $interfaceDigikey->supplier->getName()), "api_digikey"));

        return $this->redirectToRoute($redirect['route'], $redirect['params']);
    }
}
