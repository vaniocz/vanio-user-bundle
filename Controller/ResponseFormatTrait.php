<?php
namespace Vanio\UserBundle\Controller;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Vanio\WebBundle\Serializer\UnsupportedFormatException;
use Vanio\WebBundle\Templating\ResponseContext;

trait ResponseFormatTrait
{
    /**
     * @param string $view
     * @param mixed[] $parameters
     * @param Response|null $response
     * @return Response
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     */
    protected function render($view, array $parameters = [], ?Response $response = null): Response
    {
        $format = $this->requestStack()->getCurrentRequest()->getRequestFormat();
        $view = str_replace('.html.twig', '', $view);
        $this->responseContext()->clear();
        $views = [sprintf('%s.%s.twig', $view, $format), sprintf('%s.twig', $view)];

        foreach ($views as $view) {
            try {
                $viewResponse = parent::render($view, $parameters, $response);
                break;
            } catch (\Throwable $e) {
                if ($e instanceof RuntimeError && $e->getPrevious() instanceof UnsupportedFormatException) {
                    throw new UnsupportedMediaTypeHttpException($e->getPrevious()->getMessage(), $e);
                } elseif (!$e instanceof LoaderError && !$e->getPrevious() instanceof LoaderError) {
                    throw $e;
                }
            }
        }

        if (!isset($viewResponse)) {
            throw new NotFoundHttpException(sprintf('Neither of views "%s" found.', implode('", "', $views)), $e);
        }

        if ($statusCode = $this->responseContext()->statusCode()) {
            $viewResponse->setStatusCode($statusCode, $this->responseContext()->statusText());
        }

        return $viewResponse;
    }

    private function requestStack(): RequestStack
    {
        return $this->get('request_stack');
    }

    private function responseContext(): ResponseContext
    {
        return $this->get('vanio_web.templating.response_context');
    }
}
