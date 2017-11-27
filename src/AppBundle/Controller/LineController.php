<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Bom;
use AppBundle\Entity\Line;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

class LineController extends Controller
{

    /**
     * @Route("/line/delete/{id}", requirements={"id": "\d+"}, name="line_delete")
     */
    public function deleteAction(Line $line, Request $request)
    {
        $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.line.delete.success", array('%line%' => $line->getEcuPn())));

        $em = $this->getDoctrine()->getManager();
        $em->remove($line);
        $em->flush();

        return $this->redirectToRoute('bom_manage', array('id' => $line->getBom()->getId()));
    }

    /**
     * @Route("/bom/{id}/line/delete/all", requirements={"id": "\d+"}, name="line_delete_all")
     */
    public function deleteAllAction(Bom $bom, Request $request)
    {
        $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.bom.deleteAll.success", array('%bom%' => $bom->getName())));

        $em = $this->getDoctrine()->getManager();

        foreach ($bom->getLines() as $line){
            $em->remove($line);
        }

        $em->flush();

        return $this->redirectToRoute('bom_manage', array('id' => $bom->getId()));
    }

    private function getAddLineBuilder()
    {
        return $this->get('form.factory')->createNamedBuilder("form_add")
            ->add("ecuPn", TextType::class, array(
                'required' => true,
                'label' => 'line.label.ecuPn',
                'translation_domain' => 'form',
                'attr' => array(
                    'placeholder' => "line.placeholder.ecuPn",
                )
            ))
            ->add("multiplier", IntegerType::class, array(
                'required' => true,
                'label' => 'line.label.multiplier',
                'translation_domain' => 'form',
                'attr' => array(
                    'placeholder' => "line.placeholder.multiplier",
                )
            ))
            ->add("mfrName1", TextType::class, array(
                'required' => false,
                'label' => 'line.label.mfrName1',
                'translation_domain' => 'form',
                'attr' => array(
                    'placeholder' => "line.placeholder.mfrName1",
                )
            ))
            ->add("mfrPn1", TextType::class, array(
                'required' => false,
                'label' => 'line.label.mfrPn1',
                'translation_domain' => 'form',
                'attr' => array(
                    'placeholder' => "line.placeholder.mfrPn1",
                )
            ))
            ->add("mfrName2", TextType::class, array(
                'label' => 'line.label.mfrName2',
                'translation_domain' => 'form',
                'required' => false,
                'attr' => array(
                    'placeholder' => "line.placeholder.mfrName2",
                )
            ))
            ->add("mfrPn2", TextType::class, array(
                'label' => 'line.label.mfrPn2',
                'translation_domain' => 'form',
                'required' => false,
                'attr' => array(
                    'placeholder' => "line.placeholder.mfrPn2",
                )
            ))
            ->add("mfrName3", TextType::class, array(
                'label' => 'line.label.mfrName3',
                'translation_domain' => 'form',
                'required' => false,
                'attr' => array(
                    'placeholder' => "line.placeholder.mfrName3",
                )
            ))
            ->add("mfrPn3", TextType::class, array(
                'label' => 'line.label.mfrPn3',
                'translation_domain' => 'form',
                'required' => false,
                'attr' => array(
                    'placeholder' => "line.placeholder.mfrPn3",
                )
            ))
            ->add("mfrName4", TextType::class, array(
                'label' => 'line.label.mfrName4',
                'translation_domain' => 'form',
                'required' => false,
                'attr' => array(
                    'placeholder' => "line.placeholder.mfrName4",
                )
            ))
            ->add("mfrPn4", TextType::class, array(
                'label' => 'line.label.mfrPn4',
                'translation_domain' => 'form',
                'required' => false,
                'attr' => array(
                    'placeholder' => "line.placeholder.mfrPn4",
                )
            ));
    }

    public function addFormAction($bomId)
    {
        $addLineBuilder = $this->getAddLineBuilder();
        $addLineBuilder->setAction($this->generateUrl('line_add', array('id' => $bomId)));
        $addLineForm = $addLineBuilder->getForm();

        return $this->render('bom/line/add.html.twig', array(
            'form' => $addLineForm->createView(),
        ));
    }

    /**
     * @Route("/line/add/{id}", requirements={"id": "\d+"}, name="line_add")
     */
    public function lineAddAction(Bom $bom, Request $request)
    {
        $addLineForm = $this->getAddLineBuilder()->getForm();

        $addLineForm->handleRequest($request);

        if ($addLineForm->isSubmitted() && $addLineForm->isValid())
        {
            $data = $addLineForm->getData();

            $ecuPn = $data["ecuPn"];
            $multiplier = $data["multiplier"];

            if (is_string($ecuPn) && $multiplier > 0) {
                $line = new Line();
                $line->setBom($bom);

                $line->setEcuPn($data['ecuPn']);
                $line->setMultiplier($data['multiplier']);

                if (is_string($data['mfrName1']) && is_string($data['mfrPn1']))
                    $line->createAlternative($data['mfrName1'], $data['mfrPn1']);

                if (is_string($data['mfrName2']) && is_string($data['mfrPn2']))
                    $line->createAlternative($data['mfrName2'], $data['mfrPn2']);

                if (is_string($data['mfrName3']) && is_string($data['mfrPn3']))
                    $line->createAlternative($data['mfrName3'], $data['mfrPn3']);

                if (is_string($data['mfrName4']) && is_string($data['mfrPn4']))
                    $line->createAlternative($data['mfrName4'], $data['mfrPn4']);

                $bom->addLine($line);

                $this->getDoctrine()->getManager()->flush();

                $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.line.add.success", array('%line%' => $line->getEcuPn())));
            }
            else
            {
                $request->getSession()->getFlashBag()->add('danger', $this->get('translator')->trans("flash.line.add.failed"));
            }
        }
        else
        {
            $request->getSession()->getFlashBag()->add('danger', $this->get('translator')->trans("flash.line.add.failed"));
        }

        return $this->redirectToRoute('bom_manage', array('id' => $bom->getId()));
    }

    private function getExcelBuilder(){
        return $this->get('form.factory')->createNamedBuilder("form_excel")
            ->add('file', FileType::class, array(
                'label' => 'line.label.excelFile',
                'translation_domain' => 'form',
            ));
    }

    public function excelFormAction($bomId)
    {
        $addExcelBuilder = $this->getExcelBuilder();
        $addExcelBuilder->setAction($this->generateUrl('line_add_excel', array('id' => $bomId)));
        $addExcelForm = $addExcelBuilder->getForm();

        return $this->render('bom/line/excel.html.twig', array(
            'form' => $addExcelForm->createView(),
        ));
    }

    /**
     * @Route("/line/add/excel/{id}", requirements={"id": "\d+"}, name="line_add_excel")
     */
    public function lineAddExcelAction(Bom $bom, Request $request)
    {
        $addLineExcelForm = $this->getExcelBuilder()->getForm();

        $addLineExcelForm->handleRequest($request);

        if ($addLineExcelForm->isSubmitted() && $addLineExcelForm->isValid()) {
            $data = $addLineExcelForm->getData();
            $file = $data['file'];
            $location = $file->getPathName();

            $phpexcel = $this->get('phpexcel');

            $valid = false;
            $types = array('Excel2007', 'Excel5', 'Excel2003XML');
            foreach ($types as $type) {
                $reader = \PHPExcel_IOFactory::createReader($type);
                if ($reader->canRead($location)) {
                    $valid = true;
                    break;
                }
            }

            if (!$valid) {
                $request->getSession()->getFlashBag()->add('danger', $this->get('translator')->trans("flash.bom.excel.wrong"));
                return $this->redirectToRoute('bom_manage', array('id' => $bom->getId()));
            }

            $excelFile = $phpexcel->createPHPExcelObject($location);
            $sheet = $excelFile->getActiveSheet();
            $lines = $sheet->toArray();

            if (count($lines) <= 1)
            {
                $request->getSession()->getFlashBag()->add('danger', $this->get('translator')->trans("flash.bom.excel.empty"));
                return $this->redirectToRoute('bom_manage', array('id' => $bom->getId()));
            }

            $lineStart = 1;
            $rowEcuPn = 0;
            $rowMultiplier = 1;
            $rowMfrName = 2;
            $rowMfrPn = 3;

            $em = $this->getDoctrine()->getManager();

            $bomFull = $em->getRepository('AppBundle:Bom')->getBomLinesAlternatives($bom->getId());

            for ($i = $lineStart ; $i <= count($lines) - 1 ; $i++)
            {
                $line = $lines[$i];

                if(!is_null($line[$rowEcuPn]) && is_float($line[$rowMultiplier]) && !is_null($line[$rowMfrName]) && !is_null($line[$rowMfrPn]))
                {
                    $lineTarget = null;
                    foreach ($bomFull->getLines() as $lineTest)
                    {
                        if ($lineTest->getEcuPn() == $line[$rowEcuPn])
                        {
                            $lineTarget = $lineTest;
                            break;
                        }
                    }

                    if (is_null($lineTarget))
                    {
                        $newLine = new Line();
                        $newLine->setEcuPn($line[$rowEcuPn]);
                        $newLine->setMultiplier((int)$line[$rowMultiplier]);
                        $newLine->createAlternative($line[$rowMfrName], $line[$rowMfrPn]);
                        $newLine->setBom($bomFull);

                        $bomFull->addLine($newLine);
                    }
                    else
                    {
                        $lineTarget->setMultiplier((int)$line[$rowMultiplier]);
                        if (!$lineTarget->testAlternative($line[$rowMfrName], $line[$rowMfrPn]))
                        {
                            $lineTarget->createAlternative($line[$rowMfrName], $line[$rowMfrPn]);
                        }
                    }
                }
            }

            $em->flush();
        }

        return $this->redirectToRoute('bom_manage', array('id' => $bom->getId()));
    }

    private function getEditBuilder(){
        return $this->get('form.factory')->createNamedBuilder("line_edit")
            ->add("ecuPn", TextType::class, array(
                    'required' => true,
                    'label' => 'line.label.ecuPn',
                    'translation_domain' => 'form',
            ))
            ->add("multiplier", IntegerType::class, array(
                'required' => true,
                'label' => 'line.label.multiplier',
                'translation_domain' => 'form',
            ))
            ->add('lineId', HiddenType::class, array(
                'required' => true,
            ))
            ->add('bomId', HiddenType::class, array(
                'required' => true,
            ));
    }

    public function editFormAction()
    {
        $formBuilder = $this->getEditBuilder();

        $formBuilder->setAction($this->generateUrl('line_edit'));
        $form = $formBuilder->getForm();

        return $this->render('bom/line/edit.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/line/edit", name="line_edit")
     */
    public function editAction(Request $request)
    {
        $form = $this->getEditBuilder()->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $data = $form->getData();
            $bomId = $data["bomId"];
            $lineId = $data["lineId"];
            $ecuPn = $data["ecuPn"];
            $multiplier = $data["multiplier"];

            if ($lineId > 0 && is_string($ecuPn) && $multiplier > 0)
            {
                $em = $this->getDoctrine()->getManager();

                $line = $em->getRepository('AppBundle:Line')->find($lineId);
                $line->setEcuPn($ecuPn);
                $line->setMultiplier($multiplier);

                $em->flush();

                $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.line.edit.success", array('%line%' => $line->getEcuPn())));
            }
            else
            {
                $request->getSession()->getFlashBag()->add('danger', $this->get('translator')->trans("flash.line.edit.failed"));
            }

            return $this->redirectToRoute('bom_manage', array('id' => $bomId));
        }
        else
        {
            $request->getSession()->getFlashBag()->add('danger', $this->get('translator')->trans("flash.line.edit.failed"));
        }

        return $this->redirect($request->headers->get('referer'));
    }
}
