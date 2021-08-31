<?php

namespace App\Controller;

use App\Entity\Anonces;
use App\Entity\Images;
use App\Form\AnoncesType;
use App\Repository\AnoncesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/anonces")
 */
class AnoncesController extends AbstractController
{
    /**
     * @Route("/", name="anonces_index", methods={"GET"})
     */
    public function index(AnoncesRepository $anoncesRepository): Response
    {
        return $this->render('anonces/index.html.twig', [
            'anonces' => $anoncesRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="anonces_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $anonce = new Anonces();
        $form = $this->createForm(AnoncesType::class, $anonce);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // On récupère les images transmises
            $images = $form->get('images')->getData();

            // On boucle sur les images
            foreach($images as $image){
                // On génère un nouveau nom de fichier
                $fichier = md5(uniqid()) . '.' . $image->guessExtension();

                // On copie le fichier dans le dossier uploads
                $image->move(
                    $this->getParameter('images_directory'),
                    $fichier
                );

                // On stocke l'image dans la base de données (son nom)
                $img = new Images();
                $img->setName($fichier);
                $anonce->addImage($img);
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($anonce);
            $entityManager->flush();

            return $this->redirectToRoute('anonces_index');
        }

        return $this->render('anonces/new.html.twig', [
            'anonce' => $anonce,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="anonces_show", methods={"GET"})
     */
    public function show(Anonces $anonce): Response
    {
        return $this->render('anonces/show.html.twig', [
            'anonce' => $anonce,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="anonces_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Anonces $anonce): Response
    {
        $form = $this->createForm(AnoncesType::class, $anonce);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // On récupère les images transmises
            $images = $form->get('images')->getData();

            // On boucle sur les images
            foreach($images as $image){
                // On génère un nouveau nom de fichier
                $fichier = md5(uniqid()) . '.' . $image->guessExtension();

                // On copie le fichier dans le dossier uploads
                $image->move(
                    $this->getParameter('images_directory'),
                    $fichier
                );

                // On stocke l'image dans la base de données (son nom)
                $img = new Images();
                $img->setName($fichier);
                $anonce->addImage($img);
            }

            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('anonces_index');
        }

        return $this->render('anonces/edit.html.twig', [
            'anonce' => $anonce,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="anonces_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Anonces $anonce): Response
    {
        if ($this->isCsrfTokenValid('delete'.$anonce->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($anonce);
            $entityManager->flush();
        }

        return $this->redirectToRoute('anonces_index');
    }

    /**
     * @Route("/supprime/image/{id}", name="anonces_delete_image", methods={"DELETE"})
     */
    public function deleteImage(Images $image, Request $request){
        $data = json_decode($request->getContent(), true);

        // On vérifie si le token est valide
        if($this->isCsrfTokenValid('delete'.$image->getId(), $data['_token'])){
            // On récupère le nom de l'image
            $nom = $image->getName();
            // On supprime le fichier
            unlink($this->getParameter('images_directory').'/'.$nom);

            // On supprime l'entrée de la base
            $em = $this->getDoctrine()->getManager();
            $em->remove($image);
            $em->flush();

            // On répond en json
            return new JsonResponse(['success' => 1]);
        }else{
            return new JsonResponse(['error' => 'Token Invalide'], 400);
        }
    }
}