<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Bom;
use AppBundle\Entity\Quantity;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;

class QuantityController extends Controller
{
    /**
     * @Route("/quantity/delete/{id}", requirements={"id": "\d+"}, name="quantity_delete")
     */
    public function deleteAction(Quantity $quantity, Request $request)
    {
        $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.quantity.delete.success", array('%quantity%' => $quantity->getName())));

        $em = $this->getDoctrine()->getManager();
        $em->remove($quantity);
        $em->flush();

        return $this->redirectToRoute('bom_manage', array('id' => $quantity->getBom()->getId()));
    }

    private function getEditBuilder(){
        return $this->get('form.factory')->createNamedBuilder("quantity_edit")
            ->add("name", TextType::class, array(
                'required' => true,
                'label' => 'quantity.label.name',
                'translation_domain' => 'form',
            ))
            ->add("quantity", IntegerType::class, array(
                'required' => true,
                'label' => 'quantity.label.quantity',
                'translation_domain' => 'form',
            ))
            ->add("date", DateType::class, array(
                'required' => true,
                'label' => 'quantity.label.date',
                'translation_domain' => 'form',
                'widget' => 'single_text',
            ))
            ->add('quantityId', HiddenType::class, array(
                'required' => true,
            ))
            ->add('bomId', HiddenType::class, array(
                'required' => true,
            ));
    }

    public function editFormAction()
    {
        $formBuilder = $this->getEditBuilder();

        $formBuilder->setAction($this->generateUrl('quantity_edit'));
        $form = $formBuilder->getForm();

        return $this->render('bom/quantity/edit.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/quantity/edit", name="quantity_edit")
     */
    public function editAction(Request $request)
    {
        $form = $this->getEditBuilder()->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $data = $form->getData();
            $quantityId = $data["quantityId"];
            $name = $data["name"];
            $quantity = $data["quantity"];
            $date = $data["date"];

            if ($quantityId > 0 && is_string($name) && $quantity > 0 && $date instanceof \DateTime)
            {
                $em = $this->getDoctrine()->getManager();

                $quantityEntity = $em->getRepository('AppBundle:Quantity')->find($quantityId);
                $quantityEntity->setName($name);
                $quantityEntity->setQuantity($quantity);
                $quantityEntity->setDate($date);

                $em->flush();

                $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.quantity.edit.success", array('%quantity%' => $quantityEntity->getName())));
            }
            else
            {
                $request->getSession()->getFlashBag()->add('danger', $this->get('translator')->trans("flash.quantity.edit.failed"));
            }
        }
        else
        {
            $request->getSession()->getFlashBag()->add('danger', $this->get('translator')->trans("flash.quantity.edit.failed"));
        }

        return $this->redirect($request->headers->get('referer'));
    }

    private function getAddBuilder()
    {
        return $this->get('form.factory')->createNamedBuilder("quantity_add")
            ->add("name", TextType::class, array(
                'required' => true,
                'label' => 'quantity.label.name',
                'translation_domain' => 'form',
                'attr' => array(
                    'placeholder' => "quantity.placeholder.name",
                )
            ))
            ->add("quantity", IntegerType::class, array(
                'required' => true,
                'label' => 'quantity.label.quantity',
                'translation_domain' => 'form',
                'attr' => array(
                    'placeholder' => "quantity.placeholder.quantity",
                )
            ))
            ->add("date", DateType::class, array(
                'required' => true,
                'label' => 'quantity.label.date',
                'translation_domain' => 'form',
                'widget' => 'single_text',
                'attr' => array(
                    'placeholder' => "quantity.placeholder.date",
                )
            ));
    }

    public function addFormAction($bomId)
    {
        $formBuilder = $this->getAddBuilder();
        $formBuilder->setAction($this->generateUrl('quantity_add', array('id' => $bomId)));
        $form = $formBuilder->getForm();

        return $this->render('bom/quantity/add.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/quantity/add/{id}", requirements={"id": "\d+"}, name="quantity_add")
     */
    public function addAction(Bom $bom, Request $request)
    {
        $form = $this->getAddBuilder()->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $data = $form->getData();
            $name = $data["name"];
            $quantity = $data["quantity"];
            $date = $data["date"];

            if (is_string($name) && $quantity > 0 && $date instanceof \DateTime) {

                $quantityEntity = new Quantity();
                $quantityEntity->setName($name);
                $quantityEntity->setQuantity($quantity);
                $quantityEntity->setDate($date);
                $quantityEntity->setBom($bom);

                $em = $this->getDoctrine()->getManager();
                $em->persist($quantityEntity);
                $em->flush();

                $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.quantity.add.success", array('%quantity%' => $quantityEntity->getName())));
            }
            else
            {
                $request->getSession()->getFlashBag()->add('danger', $this->get('translator')->trans("flash.quantity.add.failed"));
            }
        }
        else
        {
            $request->getSession()->getFlashBag()->add('danger', $this->get('translator')->trans("flash.quantity.add.failed"));
        }

        return $this->redirectToRoute('bom_manage', array('id' => $bom->getId()));
    }
}
