<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TurboFrameRedirectSubscriber implements EventSubscriberInterface
{
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function onKernelResponse(ResponseEvent $event)
    {
        if (!$this->shouldWrapRedirect($event->getRequest(), $event->getResponse())) {
            return;
        }

        $response = new Response(null, Response::HTTP_OK, [
            'Turbo-Location' => $event->getResponse()->headers->get('Location'),
        ]);

        $event->setResponse($response);
    }

    public static function getSubscribedEvents()
    {
        return [
            ResponseEvent::class => 'onKernelResponse',
        ];
    }

    private function shouldWrapRedirect(Request $request, Response $response): bool
    {
        if (!$response->isRedirection()) {
            return false;
        }

        $location = $response->headers->get('Location');

        if ($location === $this->urlGenerator->generate('app_login')) {
            return false;
        }

        return (bool) $request->headers->get('Turbo-Frame-Redirect');
    }
}
