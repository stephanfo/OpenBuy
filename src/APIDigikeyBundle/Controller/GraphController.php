<?php

namespace APIDigikeyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class GraphController extends Controller
{
    public function graphStatusAction()
    {
        return $this->render('APIDigikeyBundle:graph:status.html.twig');
    }

    /**
     * @Route("/datagraph/status", name="api_digikey_datagraph_status")
     * @Security("has_role('ROLE_STATS')")
     */
    public function dataStatusAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $em->getFilters()->disable('userfilter');

        $codes = $em->getRepository("APIDigikeyBundle:Transaction")->getLastCode();

        return $this->json($codes);
    }

    public function graphRequestsAction()
    {
        return $this->render('APIDigikeyBundle:graph:requests.html.twig');
    }

    /**
     * @Route("/datagraph/requests", name="api_digikey_datagraph_requests")
     * @Security("has_role('ROLE_STATS')")
     */
    public function dataRequetsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $em->getFilters()->disable('userfilter');

        $requests = $em->getRepository("APIDigikeyBundle:Transaction")->getRequests();

        return $this->json($requests);
    }

    public function graphUsersAction()
    {
        return $this->render('APIDigikeyBundle:graph:users.html.twig');
    }

    /**
     * @Route("/datagraph/users", name="api_digikey_datagraph_users")
     * @Security("has_role('ROLE_STATS')")
     */
    public function dataUsersAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $em->getFilters()->disable('userfilter');

        $requests = $em->getRepository("APIDigikeyBundle:Transaction")->getUsersSuppliers();

        return $this->json($requests);
    }

    public function graphTimingAction()
    {
        return $this->render('APIDigikeyBundle:graph:timing.html.twig');
    }

    /**
     * @Route("/datagraph/timing", name="api_digikey_datagraph_timing")
     * @Security("has_role('ROLE_STATS')")
     */
    public function dataTimingAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $em->getFilters()->disable('userfilter');

        $requests = $em->getRepository("APIDigikeyBundle:Transaction")->getTiming();

        return $this->json($requests);
    }
}
