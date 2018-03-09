<?php

namespace APIExcelBundle\Controller;

use APIDigikeyBundle\Service\InterfaceDigikey;
use AppBundle\Entity\Supplier;
use AppBundle\Entity\Variable;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Spatie\ArrayToXml\ArrayToXml;

class PackageSearchController extends Controller
{
    /**
     * @Route("/digikey/packagebyquantity/pricing")
     */
    public function priceAction(InterfaceDigikey $interfaceDigikey, Request $request)
    {
        $data = array();
        $data['type'] = 'PackageSearch';
        $data['search'] = $request->request->get('search');
        $data['quantity'] = $request->request->get('quantity');
        $data['preference'] = $request->request->get('preference');
        $data['supplierId'] = $request->request->get('supplierId');

        $supplier = $this->getDoctrine()->getRepository(Supplier::class)->find($data['supplierId']);

        if ($supplier->getUser()->getApiToken() === $request->request->get('apiToken'))
        {
            $articleArray = $interfaceDigikey->packageTypeByQuantityInArticle($request->headers->get('User-Agent'), $data['search'], $data['preference'], $data['quantity'], $data['supplierId']);


            if (count($articleArray) > 0 && !is_string($articleArray))
            {
                $article = array_shift($articleArray);
                $variables = $article->getVariables();
                $variable = $variables->last();
                $supplier = $article->getSupplier();

                if(count($variable->getPrices()) >= 1)
                {
                    $pricing = $this->findBestPrice($variable->getPrices(), $data['quantity']);

                    $data['allPrices'] = array('price' => $pricing['allPrices']);

                    $data['price'] = $pricing['bestPrice']->getPrice();
                    $data['column'] = $pricing['bestPrice']->getQuantity();

                    if (!is_null($pricing['previousPrice']))
                    {
                        $data['priceBefore'] = $pricing['previousPrice']->getPrice();
                        $data['columnBefore'] = $pricing['previousPrice']->getQuantity();
                    }
                    else
                    {
                        $data['priceBefore'] = "";
                        $data['columnBefore'] = "";
                    }
                    if (!is_null($pricing['nextPrice']))
                    {
                        $data['priceAfter'] = $pricing['nextPrice']->getPrice();
                        $data['columnAfter'] = $pricing['nextPrice']->getQuantity();
                    }
                    else
                    {
                        $data['priceAfter'] = "";
                        $data['columnAfter'] = "";
                    }
                    if (!is_null($pricing['firstPrice']))
                    {
                        $data['priceFirst'] = $pricing['firstPrice']->getPrice();
                        $data['columnFirst'] = $pricing['firstPrice']->getQuantity();
                    }
                    else
                    {
                        $data['priceFirst'] = "";
                        $data['columnFirst'] = "";
                    }
                }
                else
                {
                    $data['allPrices'] = null;
                    $data['price'] = "";
                    $data['column'] = "";
                    $data['priceBefore'] = "";
                    $data['columnBefore'] = "";
                    $data['priceAfter'] = "";
                    $data['columnAfter'] = "";
                    $data['priceFirst'] = "";
                    $data['columnFirst'] = "";
                }

                $data['stock'] = $variable->getStock();
                $data['leadtime'] = $variable->getLeadtime();
                $data['status'] = $variable->getStatus();
                $data['package'] = $article->getPackage();
                $data['moq'] = $article->getMoq();
                $data['url'] = $article->getLink();
                $data['mfrPn'] = $article->getMfrPn();
                $data['mfrName'] = $article->getMfrName();
                $data['description'] = $article->getDescription();
                $data['sku'] = $article->getSku();
                $data['supplier'] = $supplier->getName();
                $data['currency'] = $supplier->getCurrency();

                $data['comment'] = "";
                $data['comment'] .= !is_null($variable->getComment()) ? $variable->getComment() . "\r\n" : "";
                $data['comment'] .= $data['search'] != $data['mfrPn'] ? "Part number corrected\r\n" : "";
                if ($article->getMoq() > 0 && ($data['quantity'] % $article->getMoq()) != 0)
                {
                    $data['comment'] .= "The quantity is not a multiple of the MOQ\r\n";
                }
                $data['comment'] .= count($articleArray) > 0 ? "Only the first price has been returned\r\n" : "";

                $data['updated'] = $variable->getCreated()->format('Y-m-d H:i:s');
            }
            else
            {
                $data['error'] = "No article returned";
            }
        }
        else
        {
            $data['error'] = "Bad token or bad Supplier ID";
        }

        $dataXml = ArrayToXml::convert($data, 'response');

        $response = new Response($dataXml);
        $response->headers->set('Content-Type', 'xml');

        return $response;
    }

    private function findBestPrice($prices, $quantity)
    {
        $firstPrice = $prices->first();
        $bestPrice = $firstPrice;
        $previousPrice = null;
        $nextPrice = null;
        $newBestPrice = false;
        $allPrices = null;

        foreach ($prices as $price) {
            $allPrices[] = array('quantity' => $price->getQuantity(), 'price' => $price->getPrice());

            if ($newBestPrice) {
                $nextPrice = $price;
                $newBestPrice = false;
            }

            if (
                (max(array($price->getQuantity(), $quantity)) * $price->getPrice())
                <
                (max(array($bestPrice->getQuantity(), $quantity)) * $bestPrice->getPrice())
            ) {
                $previousPrice = $bestPrice;
                $bestPrice = $price;
                $newBestPrice = true;
                $nextPrice = null;
            }
        }

        return array(
            'allPrices' => $allPrices,
            'firstPrice' => $firstPrice,
            'bestPrice' => $bestPrice,
            'previousPrice' => $previousPrice,
            'nextPrice' => $nextPrice
        );
    }
}
