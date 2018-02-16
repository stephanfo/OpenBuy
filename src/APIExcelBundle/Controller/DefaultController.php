<?php

namespace APIExcelBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/")
     */
    public function indexAction(Request $request)
    {
        $data = array();
        $data['type'] = 'keywordSearch';
        $data['search'] = $request->request->get('search');

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
}
