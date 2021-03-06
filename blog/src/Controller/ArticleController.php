<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Article;
use App\Entity\Tag;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use App\Service\Slugify;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("/article")
 */
class ArticleController extends AbstractController
{
    /**
     * @Route("/", name="article_index", methods={"GET"})
     * @IsGranted("ROLE_ADMIN")
     * @param ArticleRepository $articleRepository
     * @return Response
     */
    public function index(ArticleRepository $articleRepository): Response
    {
        return $this->render('article/index.html.twig', [
            'articles' => $articleRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="article_new", methods={"GET","POST"})
     * @IsGranted("ROLE_USER")
     * @param Request $request
     * @param Slugify $slugify
     * @return Response
     */
    public function new(Request $request, Slugify $slugify, \Swift_Mailer $mailer): Response
    {
        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $slug = $slugify->generate($article->getTitle());
            $article->setSlug($slug);

            $author = $this->getUser();
            $article->setAuthor($author);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($article);
            $entityManager->flush();

            $message = (new \Swift_Message('Un nouvel article vient d\'être ajouté !'))
                ->setFrom(getenv('MAILER_FROM_ADDRESS'))
                ->setTo('nawalsubject@gmail.com')
                ->setBody($this->renderView('article/email/notification.html.twig',
                    ['article' => $article]),
                    'text/html'
                );

            $mailer->send($message);

            $this->addFlash('success',
                'The article has been successfully created, and the mail has been send');

            return $this->redirectToRoute('article_index');
        }

        return $this->render('article/new.html.twig', [
            'article' => $article,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="article_show", requirements={"id"="\d+"}, methods={"GET"})
     * @param Article $article
     * @return Response
     */
    public function show(Article $article): Response
    {
        return $this->render('article/show.html.twig', [
            'article' => $article,
            'isFavorite' => $this->getUser()->isFavorite($article),
        ]);
    }

    /**
     * @Route("/{name}", name="article_showByTag", methods={"GET"})
     * @ParamConverter("tag", class="App\Entity\Tag")
     * @param Tag|null $tag
     * @return Response
     */
    public function showByTag(?Tag $tag): Response
    {
        if (!$tag) {
            throw $this
                ->createNotFoundException('No articles with this tag.');
        }

        $articles = $tag->getArticles();
        $tags = $this->getDoctrine()
            ->getRepository(Tag::class)
            ->findAll();

        return $this->render('article/showByTag.html.twig', [
            'tag' => $tag,
            'tags' => $tags,
            'articles' => $articles,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="article_edit", methods={"GET","POST"})
     * @param Request $request
     * @param Article $article
     * @param Slugify $slugify
     * @return Response
     */
    public function edit(Request $request, Article $article, Slugify $slugify): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isGranted('ROLE_ADMIN')) {
            if ($article->getAuthor()->getId() !== $user->getId()) {
                throw $this->createAccessDeniedException('YOU SHOULD NOT PASS');
            }
        }


        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $slug = $slugify->generate($article->getTitle());
            $article->setSlug($slug);
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', 'The article has been edited successfully');

            return $this->redirectToRoute('article_index', [
                'id' => $article->getId(),
            ]);
        }

        return $this->render('article/edit.html.twig', [
            'article' => $article,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="article_delete", methods={"DELETE"})
     * @IsGranted("ROLE_ADMIN")
     * @param Request $request
     * @param Article $article
     * @return Response
     */
    public function delete(Request $request, Article $article): Response
    {
        if ($this->isCsrfTokenValid('delete' . $article->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($article);
            $entityManager->flush();
            $this->addFlash('danger', 'The article has been deleted successfully');
        }


        return $this->redirectToRoute('article_index');
    }

    /**
     * @Route("/{id}/favorite", name="article_favorite", methods={"GET","POST"})
     * @param Request $request
     * @param Article $article
     * @param ObjectManager $manager
     * @return Response
     */
    public function favorite(Request $request, Article $article, ObjectManager $manager): Response
    {
        if ($this->getUser()->getFavorites()->contains($article)) {
            $this->getUser()->removeFavorite($article)   ;
        }
        else {
            $this->getUser()->addFavorite($article);
        }

        $manager->flush();

        return $this->json([
            'isFavorite' => $this->getUser()->isFavorite($article)
        ]);
    }
}
