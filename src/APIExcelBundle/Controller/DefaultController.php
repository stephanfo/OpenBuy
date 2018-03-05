<?php

namespace APIExcelBundle\Controller;

use APIDigikeyBundle\Service\InterfaceDigikey;
use AppBundle\Entity\Supplier;
use AppBundle\Entity\Variable;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/packagesearch")
     */
    public function packageSearchAction(InterfaceDigikey $interfaceDigikey, Request $request)
    {
        $data = array();
        $data['type'] = 'PackageSearch';
        $data['search'] = $request->request->get('search');
        $data['quantity'] = $request->request->get('quantity');
        $data['preference'] = $request->request->get('preference');
        $data['apiToken'] = $request->request->get('apiToken');
        $data['supplierId'] = $request->request->get('supplierId');

        $supplier = $this->getDoctrine()->getRepository(Supplier::class)->find($data['supplierId']);

        if ($supplier->getUser()->getApiToken() === $data['apiToken'])
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
                }
                else
                {
                    $data['price'] = "";
                    $data['column'] = "";
                    $data['priceBefore'] = "";
                    $data['columnBefore'] = "";
                    $data['priceAfter'] = "";
                    $data['columnAfter'] = "";
                }

                $data['stock'] = $variable->getStock();
                $data['leadtime'] = $variable->getLeadtime();
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

        $response = new Response($this->arrayToXML($data));
        $response->headers->set('Content-Type', 'xml');

        return $response;
    }

    private function arrayToXML($array) {

        $xml = new \SimpleXMLElement("<?xml version=\"1.0\"?><response></response>");

        foreach($array as $key => $value) {
            $xml->addChild("$key",htmlspecialchars("$value"));
        }

        return $xml->asXML();
    }

    private function findBestPrice($prices, $quantity)
    {
        $bestPrice = $prices->first();
        $previousPrice = null;
        $nextPrice = null;
        $newBestPrice = false;

        foreach ($prices as $price) {
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
            'bestPrice' => $bestPrice,
            'previousPrice' => $previousPrice,
            'nextPrice' => $nextPrice
        );
    }
}
