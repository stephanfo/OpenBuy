<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Config;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

class ConfigController extends Controller
{
    /**
     * @Route("/admin/configs/{page}", requirements={"page": "\d*"}, defaults={"page": "1"}, name="admin_configs_index")
     */
    public function indexAction($page, $nbPerPage = Config::NB_PER_PAGE)
    {
        $list = $this->getDoctrine()
            ->getManager()
            ->getRepository('AppBundle:Config')
            ->getConfigsPerPage($page, $nbPerPage);

        $nbPages = ceil(count($list) / $nbPerPage);

        if ($page > $nbPages && $page > 1 || $page == 0) {
            throw $this->createNotFoundException($this->get('translator')->trans("controler.page.unknow", array('%page%' => $page)));
        }

        return $this->render('admin/config/index.html.twig', [
            'list' => $list->getIterator(),
            'total' => count($list),
            'nbPages' => $nbPages,
            'page' => $page,
        ]);
    }

    /**
     * @Route("/admin/configs/edit/{id}", requirements={"id": "\d+"}, name="admin_configs_edit")
     */
    public function editAction(Config $Config, Request $request)
    {
        $form = $this->createForm(ConfigType::class, $Config);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();

            $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.Config.edit.success"), array('%Config%' => $Config->getName()));

            return $this->redirectToRoute('admin_configs_index');
        }

        return $this->render('admin/config/edit.html.twig', array(
            'form' => $form->createView(),
            '$Config' => $Config,
        ));
    }
}