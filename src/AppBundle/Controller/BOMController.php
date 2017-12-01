<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Quantity;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

use AppBundle\Entity\Bom;
use AppBundle\Form\BomType;

class BomController extends Controller
{
    /**
     * @Route("/bom", defaults={"page": "1"}, name="bom")
     * @Route("/bom/{page}", requirements={"page": "\d*"}, defaults={"page": "1"}, name="bom_index")
     */
    public function indexAction($page, $nbPerPage = Bom::NB_PER_PAGE)
    {
        $list = $this->getDoctrine()
            ->getManager()
            ->getRepository('AppBundle:Bom')
            ->getBomsPerPage($page, $nbPerPage);

        $nbPages = ceil(count($list) / $nbPerPage);

        if ($page > $nbPages && $page > 1 || $page == 0) {
            throw $this->createNotFoundException($this->get('translator')->trans("controler.page.unknow", array('%page%' => $page)));
        }

        return $this->render('bom/index.html.twig', [
            'list' => $list->getIterator(),
            'total' => count($list),
            'nbPages' => $nbPages,
            'page' => $page,
        ]);
    }

    /**
     * @Route("/bom/new", name="bom_new")
     * @Route("/bom/new/{ecuId}", requirements={"ecuId": "\d*"}, name="bom_new")
     */
    public function newAction($ecuId = null, Request $request)
    {
        $bom = new Bom();

        if (!is_null($ecuId))
        {
            $bom->setEcu($this->getDoctrine()->getRepository('AppBundle:Ecu')->find($ecuId));
        }

        $form = $this->createForm(BomType::class, $bom);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($bom);
            $em->flush();

            $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.bom.new.success", array('%bom%' => $bom->getName())));

            return $this->redirectToRoute('bom_manage', array('id' => $bom->getId()));
        }

        return $this->render('bom/new.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/bom/edit/{id}", requirements={"id": "\d+"}, name="bom_edit")
     */
    public function editAction(Bom $bom, Request $request)
    {
        $form = $this->createForm(BomType::class, $bom);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();

            $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.bom.edit.success", array('%bom%' => $bom->getName())));

            return $this->redirectToRoute('bom_index');
        }

        return $this->render('bom/edit.html.twig', array(
            'form' => $form->createView(),
            '$bom' => $bom,
        ));
    }

    /**
     * @Route("/bom/delete/{id}", requirements={"id": "\d+"}, name="bom_delete")
     */
    public function deleteAction(Bom $bom, Request $request)
    {
        $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.bom.delete.success", array('%bom%' => $bom->getName())));

        $em = $this->getDoctrine()->getManager();
        $em->remove($bom);
        $em->flush();

        return $this->redirectToRoute('bom_index');
    }

    /**
     * @Route("/bom/manage/{id}", requirements={"id": "\d+"}, name="bom_manage")
     * @Route("/bom/manage/{id}/quantity/{quantityId}", requirements={"id": "\d+", "quantityId": "\d+"}, name="bom_manage_quantity")
     */
    public function manageAction($id, $quantityId = null)
    {
        $em = $this->getDoctrine()->getManager();

        $bom = $em->getRepository('AppBundle:Bom')->getBomFullDetails($id);

        if(is_null($quantityId))
        {
            $quantities = $bom->getQuantities();

            if(count($quantities) > 0)
            {
                $quantity = $quantities[0]->getQuantity();
                $quantityId = $quantities[0]->getId();
            }
            else
            {
                $quantity = 1;
            }
        }
        else
        {
            $quantity = $em->getRepository('AppBundle:Quantity')->find($quantityId)->getQuantity();
        }

        $pricing = $this->bomPricing($bom, $quantity);

        return $this->render('bom/manage.html.twig', array(
            'bom' => $bom,
            'quantityId' => $quantityId,
            'quantity' => $quantity,
            'pricing' => $pricing,
        ));
    }

    private function bomPricing(Bom $bom, int $quantity)
    {
        $pricing = null;
        $total = 0;

        foreach ($bom->getLines() as $line)
        {
            $quantityLine = $line->getMultiplier() * $quantity;

            $bestLinePrice = null;
            $bestAlternative = null;

            foreach ($line->getAlternatives() as $alternative)
            {
                $bestPrice = null;

                foreach ($alternative->getArticles() as $article)
                {
                    foreach ($article->getVariables() as $variable)
                    {
                        foreach ($variable->getPrices() as $price)
                        {
                            if(!is_null($bestPrice))
                            {
                                if($quantityLine > $bestPrice->getQuantity())
                                {
                                    $bestTotal = $bestPrice->getPrice() * $bestPrice->getVariable()->getArticle()->getSupplier()->getExchangeRate() * $quantityLine;
                                }
                                else
                                {
                                    $bestTotal = $bestPrice->getPrice() * $bestPrice->getVariable()->getArticle()->getSupplier()->getExchangeRate()  * $bestPrice->getQuantity();
                                }

                                if($quantityLine > $price->getQuantity())
                                {
                                    $currentTotal = $price->getPrice() * $price->getVariable()->getArticle()->getSupplier()->getExchangeRate() * $quantityLine;
                                }
                                else
                                {
                                    $currentTotal = $price->getPrice() * $price->getVariable()->getArticle()->getSupplier()->getExchangeRate() * $price->getQuantity();
                                }

                                if ($currentTotal < $bestTotal)
                                {
                                    $bestPrice = $price;
                                }
                            }
                            elseif ($price->getQuantity() > 0 && $price->getPrice() > 0)
                            {
                                $bestPrice = $price;
                            }
                        }
                    }
                }

                if (is_null($bestPrice))
                {
                    $bestAlternativeOffer = array(
                        'unitPrice' => null,
                        'lineTotal' => null,
                        'currency' => null,
                        'articleId' => null,
                        'priceId' => null,
                    );
                }
                else
                {
                    $bestAlternativeOffer = array(
                        'unitPrice' => $bestPrice->getPrice(),
                        'lineTotal' => $bestPrice->getPrice() * $quantityLine,
                        'currency' => $bestPrice->getVariable()->getArticle()->getSupplier()->getCurrency(),
                        'articleId' => $bestPrice->getVariable()->getArticle()->getId(),
                        'priceId' => $bestPrice->getId(),
                    );
                }

                if(is_null($bestLinePrice) || (!is_null($bestPrice) && $bestPrice < $bestLinePrice))
                {
                    $bestLinePrice = $bestPrice;
                    $bestAlternative = $alternative;
                }

                $pricing["alternative"][$alternative->getId()] = $bestAlternativeOffer;
            }

            if(is_null($bestLinePrice))
            {
                $bestLineOffer = array(
                    'unitPrice' => null,
                    'lineTotal' => null,
                    'currency' => null,
                    'alternativeId' => null,
                );
            }
            else
            {
                $bestLineOffer = array(
                    'unitPrice' => $bestLinePrice->getPrice(),
                    'lineTotal' => $bestLinePrice->getPrice() * $quantityLine,
                    'currency' => $bestLinePrice->getVariable()->getArticle()->getSupplier()->getCurrency(),
                    'alternativeId' => $bestAlternative->getId(),
                );

                $total += $bestLinePrice->getPrice() * $bestLinePrice->getVariable()->getArticle()->getSupplier()->getExchangeRate() * $quantityLine;
            }

            $pricing["line"][$line->getId()] = $bestLineOffer;
        }

        $pricing["total"] = $total;

        return $pricing;
    }
}
