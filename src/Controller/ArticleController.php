<?php

namespace App\Controller;

// src/Controller/ArticleController.php

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Article;
use App\Form\ArticleType;

class ArticleController extends AbstractController
{
    /**
     * @Route("/articles", name="article_list", methods={"GET"})
     */
    public function index(Request $request): Response
    {
        // Get query parameters for filtering and pagination
        $status = $request->query->get('status');
        $date = $request->query->get('date');
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        // Fetch articles from the database with optional filtering by status and date
        $repository = $this->getDoctrine()->getRepository(Article::class);
        $qb = $repository->createQueryBuilder('a');
        if ($status) {
            $qb->andWhere('a.status = :status')
                ->setParameter('status', $status);
        }
        if ($date) {
            $qb->andWhere('DATE(a.publicationDate) = :date')
                ->setParameter('date', $date);
        }
        $qb->orderBy('a.createdAt', 'DESC');
        $qb->setFirstResult(($page - 1) * $limit);
        $qb->setMaxResults($limit);
        $articles = $qb->getQuery()->getResult();

        // Serialize the articles to the requested format (json, xml, csv)
        $format = $request->query->get('format', 'json');
        switch ($format) {
            case 'xml':
                $data = $this->get('serializer')->serialize($articles, 'xml');
                $response = new Response($data);
                $response->headers->set('Content-Type', 'application/xml');
                break;
            case 'csv':
                $data = $this->get('serializer')->serialize($articles, 'csv');
                $response = new Response($data);
                $response->headers->set('Content-Type', 'text/csv');
                break;
            default:
                $data = $this->get('serializer')->normalize($articles);
                $response = $this->json($data);
                break;
        }

        return $response;
    }

    /**
     * @Route("/articles", name="article_create", methods={"POST"})
     */
    public function create(Request $request): Response
    {
        // Create a new Article object
        $article = new Article();

        // Create a form to handle the article data
        $form = $this->createForm(ArticleType::class, $article);

        // Submit the form with the request data
        $form->submit($request->request->all());

        // Validate the form
        if ($form->isValid()) {
            // Set the creation date
            $article->setCreatedAt(new \DateTimeImmutable());

            // Persist the article to the database
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($article);
            $entityManager->flush();

            // Return a success response
            return $this->json(['message' => 'Article created successfully'], Response::HTTP_CREATED);
        } else {
            // Return a validation error response
            $errors = $this->get('serializer')->normalize($form->getErrors(true));
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }
    }
}
