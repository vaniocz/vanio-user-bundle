<?php
namespace Vanio\UserBundle\Controller;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

trait ResponseFormatTrait
{
    /**
     * @param string $view
     * @param mixed[] $parameters
     * @param Response|null $response
     * @return Response
     */
    protected function render($view, array $parameters = [], Response $response = null): Response
    {
        $format = $this->requestStack()->getCurrentRequest()->getRequestFormat();

        try {
            $view = str_replace('.html.twig', sprintf('.%s.twig', $format), $view);
            return parent::render($view, $parameters, $response);
        } catch (\InvalidArgumentException $e) {
            throw $this->createNotFoundException(sprintf('Template for format "%s" not found.', $format), $e);
        }
    }

    private function requestStack(): RequestStack
    {
        return $this->get('request_stack');
    }
}
