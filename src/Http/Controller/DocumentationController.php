<?php

namespace Dedoc\Scramble\Http\Controller;

use Dedoc\Scramble\Generator;
use Dedoc\Scramble\Scramble;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

/**
 * Controller for serving Scramble documentation UI and JSON specification.
 */
class DocumentationController extends AbstractController
{
    public function __construct(
        private Generator $generator,
        private Environment $twig
    ) {}

    /**
     * Serve the documentation UI.
     */
    #[Route('/docs/api', name: 'scramble.docs.ui', methods: ['GET'])]
    public function ui(string $api = 'default'): Response
    {
        $config = Scramble::getGeneratorConfig($api);
        $spec = ($this->generator)($config);

        return new Response(
            $this->twig->render('@Scramble/docs.html.twig', [
                'spec' => $spec,
                'config' => $config,
            ])
        );
    }

    /**
     * Serve the OpenAPI JSON specification.
     */
    #[Route('/docs/api.json', name: 'scramble.docs.document', methods: ['GET'])]
    public function specification(string $api = 'default'): JsonResponse
    {
        $config = Scramble::getGeneratorConfig($api);
        $spec = ($this->generator)($config);

        return new JsonResponse($spec, 200, [], JSON_PRETTY_PRINT);
    }
}
