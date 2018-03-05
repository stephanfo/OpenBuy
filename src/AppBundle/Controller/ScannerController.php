<?php

namespace AppBundle\Controller;

use APIDigikeyBundle\Service\InterfaceDigikey;
use AppBundle\Entity\Alternative;
use AppBundle\Entity\Bom;
use AppBundle\Entity\Line;
use AppBundle\Entity\Supplier;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ScannerController extends Controller
{
    /**
     * @Route("/scanner/alternative/{id}", requirements={"id": "\d+"}, name="scanner_alternative")
     */
    public function scanAlternativeAction(Alternative $alternative, InterfaceDigikey $interfaceDigikey, Request $request)
    {

        $response = $this->scanAlternative($alternative, $interfaceDigikey, $request);

        if(is_string($response))
        {
            $request->getSession()->getFlashBag()->add('danger', $this->get('translator')->trans("flash.scanner.alternative.failed", array('%error%' => $response)));
            return $this->redirect($request->headers->get('referer'));
        }

        $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.scanner.alternative.success", array('%alternative%' => $alternative->getMfrPn())));

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @Route("/scanner/line/{id}", requirements={"id": "\d+"}, name="scanner_line")
     */
    public function scanLineAction(Line $line, InterfaceDigikey $interfaceDigikey, Request $request)
    {

        $session = $request->getSession();

        $alternatives = $line->getAlternatives();

        $session->set('scan_length', count($alternatives));
        $session->set('scan_current', 0);
        $session->set('scan_reload', false);
        $i = 0;

        foreach ($alternatives as $key => $alternative) {
            $session->set('scan_current', ++$i);
            $response = $this->scanAlternative($alternative, $interfaceDigikey, $request);

            if(is_string($response))
            {
                $request->getSession()->getFlashBag()->add('danger', $this->get('translator')->trans("flash.scanner.line.failed", array('%error%' => $response)));
                return $this->redirect($request->headers->get('referer'));
            }
        }

        $session->set('scan_reload', true);

        $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.scanner.line.success", array('%line%' => $line->getEcuPn())));

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @Route("/scanner/bom/{id}", requirements={"id": "\d+"}, name="scanner_bom")
     */
    public function scanBomAction(Bom $bom, InterfaceDigikey $interfaceDigikey, Request $request)
    {

        $session = $request->getSession();

        $lines = $bom->getLines();

        $session->set('scan_length', count($lines));
        $session->set('scan_current', 0);
        $session->set('scan_reload', false);
        $session->save();

        $i = 0;

        foreach ($lines as $key => $line)
        {
            $session->set('scan_current', ++$i);
            $session->save();

            $alternatives = $line->getAlternatives();

            foreach ($alternatives as $key => $alternative) {
                $response = $this->scanAlternative($alternative, $interfaceDigikey, $request);

                if(is_string($response))
                {
                    $request->getSession()->getFlashBag()->add('danger', $this->get('translator')->trans("flash.scanner.bom.failed", array('%error%' => $response)));
                    return $this->redirect($request->headers->get('referer'));
                }

            }
        }
        $session->set('scan_reload', true);
        $session->save();

        $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.scanner.bom.success", array('%bom%' => $bom->getName())));

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @Route("/scanner/status", name="scanner_status")
     */
    public function statusAction(Request $request)
    {
        $session = $request->getSession();

        if ($session->has('scan_length') && $session->has('scan_current') && $session->has('scan_reload'))
        {
            $response = array(
                'scanLength' => $session->get('scan_length'),
                'scanCurrent' => $session->get('scan_current'),
                'scanReload' => $session->get('scan_reload'),
            );

            if ($session->get('scan_reload') === true)
            {
                $session->remove('scan_length');
                $session->remove('scan_current');
                $session->remove('scan_reload');
            }
        }
        else
        {
            $response = null;
        }

        return $this->json($response);
    }


    private function scanAlternative(Alternative $alternative, InterfaceDigikey $interfaceDigikey, Request $request)
    {
        $suppliers = $this->getDoctrine()->getRepository(Supplier::class)->getSuppliersWithAPI();

        foreach ($alternative->getArticles() as $article)
        {
            $alternative->removeArticle($article);
        }

        foreach($suppliers as $supplier)
        {
            $articleArray = $interfaceDigikey->search($request->headers->get('User-Agent'), $alternative->getMfrPn(), $supplier->getId());

            if(is_string($articleArray))
                return $articleArray;

            foreach ($articleArray as $article) {
                $alternative->addArticle($article);
            }
        }

        $this->getDoctrine()->getManager()->flush();
    }
}
