<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CategoryController extends AbstractController
{
    /**
     * @Route("/category", name="category")
     * @param Request $request
     * @return Response
     */
    public function add(Request $request) : Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class,$category);
        $form->handleRequest($request);

        $categories = $this->getDoctrine()
            ->getRepository(Category::class)
            ->findAll();

        if ($form->isSubmitted()) {
            $categoryManager = $this->getDoctrine()->getManager();
            $categoryManager->persist($category);
            $categoryManager->flush();

            return $this->redirectToRoute('category');
        }

        return $this->render('category/index.html.twig', [
            'form' => $form->createView(),
            'categories' => $categories,
        ]);
    }
}