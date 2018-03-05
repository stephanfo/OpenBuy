<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Alternative;
use AppBundle\Entity\Line;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;

class AlternativeController extends Controller
{

    /**
     * @Route("/alternative/delete/{id}", requirements={"id": "\d+"}, name="alternative_delete")
     */
    public function deleteAction(Alternative $alternative, Request $request)
    {
        $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.alternative.delete.success", array('%alternative%' => $alternative->getMfrPn())));

        $bomId = $alternative->getLine()->getBom()->getId();

        $em = $this->getDoctrine()->getManager();
        $em->remove($alternative);
        $em->flush();

        return $this->redirectToRoute('bom_manage', array('id' => $bomId));
    }

    private function getEditBuilder(){
        return $this->get('form.factory')->createNamedBuilder("alternative_edit")
            ->add("mfrName", TextType::class, array(
                'required' => true,
                'label' => 'alternative.label.mfrName',
                'translation_domain' => 'form',
            ))
            ->add("mfrPn", TextType::class, array(
                'required' => true,
                'label' => 'alternative.label.mfrPn',
                'translation_domain' => 'form',
            ))
            ->add('alternativeId', HiddenType::class, array(
                'required' => true,
            ))
            ->add('bomId', HiddenType::class, array(
                'required' => true,
            ));
    }

    public function editFormAction()
    {
        $formBuilder = $this->getEditBuilder();

        $formBuilder->setAction($this->generateUrl('alternative_edit'));
        $form = $formBuilder->getForm();

        return $this->render('bom/alternative/edit.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/alternative/edit", name="alternative_edit")
     */
    public function editAction(Request $request)
    {
        $form = $this->getEditBuilder()->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $data = $form->getData();
            $bomId = $data["bomId"];
            $alternativeId = $data["alternativeId"];
            $mfrName = $data["mfrName"];
            $mfrPn = $data["mfrPn"];

            if ($alternativeId > 0 && is_string($mfrName) && is_string($mfrPn))
            {
                $em = $this->getDoctrine()->getManager();

                $alternative = $em->getRepository(Alternative::class)->find($alternativeId);
                $alternative->setMfrName($mfrName);
                $alternative->setMfrPn($mfrPn);

                $em->flush();

                $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.alternative.edit.success", array('%alternative%' => $alternative->getMfrPn())));
            }
            else
            {
                $request->getSession()->getFlashBag()->add('danger', $this->get('translator')->trans("flash.alternative.edit.failed"));
            }

            return $this->redirectToRoute('bom_manage', array('id' => $bomId));
        }
        else
        {
            $request->getSession()->getFlashBag()->add('danger', $this->get('translator')->trans("flash.alternative.edit.failed"));
        }

        return $this->redirect($request->headers->get('referer'));

    }

    private function getAddBuilder()
    {
        return $this->get('form.factory')->createNamedBuilder("alternative_add")
            ->add("mfrName1", TextType::class, array(
                'required' => true,
                'label' => 'alternative.label.mfrName1',
                'translation_domain' => 'form',
                'attr' => array(
                    'placeholder' => "alternative.placeholder.mfrName1",
                )
            ))
            ->add("mfrPn1", TextType::class, array(
                'required' => true,
                'label' => 'alternative.label.mfrPn1',
                'translation_domain' => 'form',
                'attr' => array(
                    'placeholder' => "alternative.placeholder.mfrPn1",
                )
            ))
            ->add("mfrName2", TextType::class, array(
                'required' => false,
                'label' => 'alternative.label.mfrName2',
                'translation_domain' => 'form',
                'attr' => array(
                    'placeholder' => "alternative.placeholder.mfrName2",
                )
            ))
            ->add("mfrPn2", TextType::class, array(
                'required' => false,
                'label' => 'alternative.label.mfrPn2',
                'translation_domain' => 'form',
                'attr' => array(
                    'placeholder' => "alternative.placeholder.mfrPn2",
                )
            ))
            ->add("mfrName3", TextType::class, array(
                'required' => false,
                'label' => 'alternative.label.mfrName3',
                'translation_domain' => 'form',
                'attr' => array(
                    'placeholder' => "alternative.placeholder.mfrName3",
                )
            ))
            ->add("mfrPn3", TextType::class, array(
                'required' => false,
                'label' => 'alternative.label.mfrPn3',
                'translation_domain' => 'form',
                'attr' => array(
                    'placeholder' => "alternative.placeholder.mfrPn3",
                )
            ))
            ->add("mfrName4", TextType::class, array(
                'required' => false,
                'label' => 'alternative.label.mfrName4',
                'translation_domain' => 'form',
                'attr' => array(
                    'placeholder' => "alternative.placeholder.mfrName4",
                )
            ))
            ->add("mfrPn4", TextType::class, array(
                'required' => false,
                'label' => 'alternative.label.mfrPn4',
                'translation_domain' => 'form',
                'attr' => array(
                    'placeholder' => "alternative.placeholder.mfrPn4",
                )
            ))
            ->add('lineId', HiddenType::class, array(
                'required' => true,
            ))
            ->add('bomId', HiddenType::class, array(
                'required' => true,
            ));
    }

    public function addFormAction()
    {
        $formBuilder = $this->getAddBuilder();

        $formBuilder->setAction($this->generateUrl('alternative_add'));
        $form = $formBuilder->getForm();

        return $this->render('bom/alternative/add.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/alternative/add", name="alternative_add")
     */
    public function addAction(Request $request)
    {
        $form = $this->getAddBuilder()->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $data = $form->getData();
            $bomId = $data["bomId"];
            $lineId = $data["lineId"];

            $em = $this->getDoctrine()->getManager();
            $line = $em->getRepository(Line::class)->find($lineId);

            if (is_string($data['mfrName1']) && is_string($data['mfrPn1']))
                $line->createAlternative($data['mfrName1'], $data['mfrPn1']);

            if (is_string($data['mfrName2']) && is_string($data['mfrPn2']))
                $line->createAlternative($data['mfrName2'], $data['mfrPn2']);

            if (is_string($data['mfrName3']) && is_string($data['mfrPn3']))
                $line->createAlternative($data['mfrName3'], $data['mfrPn3']);

            if (is_string($data['mfrName4']) && is_string($data['mfrPn4']))
                $line->createAlternative($data['mfrName4'], $data['mfrPn4']);

            $em->flush();

            $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.alternative.add.success", array('%line%' => $line->getEcuPn())));

            return $this->redirectToRoute('bom_manage', array('id' => $bomId));
        }
        else
        {
            $request->getSession()->getFlashBag()->add('danger', $this->get('translator')->trans("flash.alternative.add.failed"));
        }

        return $this->redirect($request->headers->get('referer'));
    }

}
