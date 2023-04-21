<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

class ArticleSerializer
{
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function serializeArticles($articles, $format)
    {
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
                $response = new Response($this->serializer->serialize($data, 'json'));
                $response->headers->set('Content-Type', 'application/json');
                break;
        }

        return $response;
    }
}
