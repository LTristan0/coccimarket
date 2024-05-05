<?php

namespace App\Controller;

use TCPDF;
use DateTimeImmutable;
use App\Entity\Formation;
use App\Form\Formation1Type;
use App\Services\ImageUploaderHelper;
use App\Repository\FormationRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/produit')]
class FormationController extends AbstractController
{
    #[Route('/pdf/{id}', name: 'app_formation_pdf', methods: ['GET'])]
    public function pdf(Formation $produit): Response
    {
        $pdf = new TCPDF();

        $left_column = '<img src="images/fcpro.jpg">' . 'Tarif : ' . $produit->getPrice() . ' €' . '<br><br>' . 'Places : ' . $produit->getCapacity();
        $right_column = '<b><u>Description de la produit : </u></b><br><br>' . $produit->getDescription() . '<br><br><br>' . '<b><u>Contenu de la produit : </u></b>' . $produit->getContent();
        $y = $pdf->getY();
        $middle = $pdf->getPageWidth() / 2;
        $name = '<b><i>' . $produit->getName() . '</i></b>';

        $pdf->SetTitle($produit->getName());
        $pdf->setCellPaddings(1, 1, 1, 1);
        $pdf->setCellMargins(1, 1, 1, 1);

        $pdf->AddPage();

        $pdf->setXY(10, 1);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->writeHTMLCell(125, '', '', $y, 'Date de création : 01/01/2023', 0, 0, 1, true, 'J', true);

        $pdf->setXY($middle, 1);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->writeHTMLCell(125, '', '', $y, 'Date de mise à jour : 01/01/2023', 0, 0, 1, true, 'J', true);

        $pdf->setXY($middle-30, 15);
        $pdf->SetFont('helvetica', '', 18);
        $pdf->SetTextColor(1, 14, 51);
        $pdf->SetFillColor(212, 225, 237);
        $pdf->writeHTMLCell(125, '', '', $y, $name, 0, 0, 1, true, 'J', true);

        $pdf->SetFillColor(234, 232, 232);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->setXY(10, 15);
        $pdf->writeHTMLCell(60, '', '', $y, $left_column, 0, 0, 1, true, 'J', true);

        $pdf->SetFont('helvetica', '', 9);
        $pdf->setXY($middle-30, 30);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->writeHTMLCell(125, '', '', $y, $right_column, 0, 0, 1, true, 'J', true);


        return $pdf->Output('fcpro-produit-' . $produit->getId() . '.pdf', 'I');
    }

    #[Route('/futur', name: 'app_formation_futur', methods: ['GET'])]
    public function futur(FormationRepository $formationRepository): Response
    {
        $formationsPerThree = array();

        $produits = $formationRepository->findAllInTheFuture();

        $i = 0;
        $j = 1;
        foreach ($produits as $produit){
            $i++;
            if($i>3){
            $j++; $i=1;
            }
        $formationsPerThree[$j][$i] = $produit;
        }
        dump($produit);
        dump($formationsPerThree);

        return $this->render('produit/futur.html.twig', [
            'produits' => $formationsPerThree,
        ]);
    }

    #[Route('/{id}/duplicate', name: 'app_formation_duplicate', methods: ['GET', 'POST'])]
    public function duplicate(Request $request, FormationRepository $formationRepository, TranslatorInterface $translator, Formation $produit): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $formation2 = new Formation();
        $formation2->setCreatedAt($produit->getCreatedAt());
        $formation2->setCreatedBy($produit->getCreatedBy());
        $formation2->setContent($produit->getContent());
        $formation2->setDescription($produit->getDescription());

        $formation2->setCapacity($produit->getCapacity());
        $formation2->setStartDateTime($produit->getStartDateTime());
        $formation2->setEndDateTime($produit->getEndDateTime());
        $formation2->setImageFileName($produit->getImageFileName());
        $formation2->setName($produit->getName());
        $formation2->setPrice($produit->getPrice());

        $formationRepository->save($formation2, true);
        $this->addFlash('success', $translator->trans('The produit is copied'));

        return $this->redirectToRoute('app_formation_index');
    }

    /* #[Route('/futur', name: 'app_formation_futur', methods: ['GET'])]
    public function futur(FormationRepository $formationRepository): Response
    {
        return $this->render('produit/futur.html.twig', [
            'produits' => $formationRepository->findAllInTheFuture(),
        ]);
    }
    */

    #[Route('/catalog', name: 'app_formation_catalog', methods: ['GET'])]
    public function catalog(FormationRepository $formationRepository): Response
    {
        return $this->render('produit/catalog.html.twig', [
            'produits' => $formationRepository->findAllInTheFuture(),
        ]);
    }

    #[Route('/', name: 'app_formation_index', methods: ['GET'])]
    public function index(FormationRepository $formationRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        return $this->render('produit/index.html.twig', [
            'produits' => $formationRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_formation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ImageUploaderHelper $imageUploaderHelper, FormationRepository $formationRepository, TranslatorInterface $translator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $produit = new Formation();
        $produit->setCreatedAt(new DateTimeImmutable());
        $produit->setCreatedBy($this->getUser());

        $form = $this->createForm(Formation1Type::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $errorMessage = $imageUploaderHelper->uploadImage($form, $produit);
            if (!empty($errorMessage)) {
                $this->addFlash ('danger', $translator->trans('An error has occured: ') . $errorMessage);
            }
            $formationRepository->save($produit, true);

            return $this->redirectToRoute('app_formation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('produit/new.html.twig', [
            'produit' => $produit,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_formation_show', methods: ['GET'])]
    public function show(Formation $produit): Response
    {
        return $this->render('produit/show.html.twig', [
            'produit' => $produit,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_formation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ImageUploaderHelper $imageUploaderHelper, FormationRepository $formationRepository, Formation $produit, TranslatorInterface $translator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $form = $this->createForm(Formation1Type::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $errorMessage = $imageUploaderHelper->uploadImage($form, $produit);
            if (!empty($errorMessage)) {
                $this->addFlash ('danger', $translator->trans('An error has occured: ') . $errorMessage);
            }
            $formationRepository->save($produit, true);

            return $this->redirectToRoute('app_formation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('produit/edit.html.twig', [
            'produit' => $produit,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_formation_delete', methods: ['POST'])]
    public function delete(Request $request, Formation $produit, FormationRepository $formationRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        if ($this->isCsrfTokenValid('delete'.$produit->getId(), $request->request->get('_token'))) 
        {
            $formationRepository->remove($produit, true);
        }

        return $this->redirectToRoute('app_formation_index', [], Response::HTTP_SEE_OTHER);
    }
}
