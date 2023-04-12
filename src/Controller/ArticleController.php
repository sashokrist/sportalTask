<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Article;
use App\Form\ArticleType;
use Symfony\Component\Serializer\SerializerInterface;

class ArticleController extends AbstractController
{
    private $entityManager;
    private $serializer;

    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer)
    {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
    }

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

        // Fetch database with filter: status and date
        $repository = $this->entityManager->getRepository(Article::class);
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

        $format = $request->query->get('format', 'json');
        switch ($format) {
            case 'xml':
                $data = $this->serializer->serialize($articles, 'xml');
                $response = new Response($data);
                $response->headers->set('Content-Type', 'application/xml');
                break;
            case 'csv':
                $data = $this->serializer->serialize($articles, 'csv');
                $response = new Response($data);
                $response->headers->set('Content-Type', 'text/csv');
                break;
            default:
                $data = $this->serializer->normalize($articles);
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
        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article, array('csrf_protection' => false));
        $form->submit($request->request->all());

        if ($form->isValid()) {
            $article->setCreatedAt(new \DateTimeImmutable());
            $article->setPublicationDate(new \DateTimeImmutable());

            $this->entityManager->persist($article);
            $this->entityManager->flush();

            return $this->json(['message' => 'Article created successfully'], Response::HTTP_CREATED);
        }

        $errors = $this->serializer->normalize($form->getErrors(true));
        return $this->json($errors, Response::HTTP_BAD_REQUEST);
    }
}
