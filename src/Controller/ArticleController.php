<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use App\Service\ArticleSerializer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ArticleController extends AbstractController
{
    private $entityManager;
    private $articleSerializer;

    public function __construct(EntityManagerInterface $entityManager, ArticleSerializer $articleSerializer)
    {
        $this->entityManager = $entityManager;
        $this->articleSerializer = $articleSerializer;
    }

    /**
     * @Route("/articles", name="article_list", methods={"GET"})
     */
    public function index(Request $request): Response
    {
        $status = $request->get('status');
        $date = $request->get('date');
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        $repository = $this->entityManager->getRepository(Article::class);
        $articles = $repository->findByFilters($status, $date, $page, $limit);

        $format = $request->get('format', 'json');

        return $this->articleSerializer->serializeArticles($articles, $format);
    }

    /**
     * @Route("/articles", name="article_create", methods={"POST"})
     */
    public function create(Request $request, ArticleRepository $repository): Response
    {
        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article, ['csrf_protection' => false]);
        $form->submit($request->request->all());

        if ($form->isValid()) {
            $article->setCreatedAt(new \DateTime());
            $article->setPublicationDate(new \DateTime());

            $this->entityManager->persist($article);
            $this->entityManager->flush();

            return $this->json(['message' => 'Article created successfully'], Response::HTTP_CREATED);
        }

        $errors = $this->serializer->normalize($form->getErrors(true));

        return $this->json($errors, Response::HTTP_BAD_REQUEST);
    }
}
