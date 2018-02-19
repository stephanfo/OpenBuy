<?php

namespace APIExcelBundle\Controller;

use APIDigikeyBundle\Service\InterfaceDigikey;
use AppBundle\Entity\Supplier;
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
        $data['supplierId'] = $request->request->get('supplierId');

        $articleArray = $interfaceDigikey->packageTypeByQuantityInArticle($request->headers->get('User-Agent'), $data['search'], $data['preference'], $data['quantity'], $data['supplierId']);

        if (count($articleArray) > 0 && !is_string($articleArray))
        {
            $article = array_shift($articleArray);
            $variables = $article->getVariables();
            $variable = $variables->last();
            $supplier = $article->getSupplier();

            if(count($variable->getPrices()) >= 1)
            {
                $price = $this->findBestPrice($variable->getPrices(), $data['quantity']);

                $data['price'] = $price->getPrice();
                $data['column'] = $price->getQuantity();
            }
            else
            {
                $data['price'] = "";
                $data['column'] = "";
            }

            $data['stock'] = $variable->getStock();
            $data['leadtime'] = $variable->getLeadtime();
            $data['package'] = $article->getPackage();
            $data['url'] = $article->getLink();
            $data['mfrPn'] = $article->getMfrPn();
            $data['mfrName'] = $article->getMfrName();
            $data['description'] = $article->getDescription();
            $data['sku'] = $article->getSku();
            $data['supplier'] = $supplier->getName();
            $data['currency'] = $supplier->getCurrency();
            $data['comment'] = $data['search'] != $data['mfrPn'] ? "Part number corrected, please check is ok" : "";
        }
        else
        {
            $data['error'] = "No article returned";
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

        foreach ($prices as $price) {
            if (
                (max(array($price->getQuantity(), $quantity)) * $price->getPrice())
                <
                (max(array($bestPrice->getQuantity(), $quantity)) * $bestPrice->getPrice())
            ) {
                $bestPrice = $price;
            }
        }

        return $bestPrice;
    }
}
