<?php

namespace Anboo\ApiBundle\Controller;

use EXSyst\Component\Swagger\Collections\Paths;
use Nelmio\ApiDocBundle\ApiDocGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webslon\Bundle\ApiBundle\Util\PropertyAccessor;
use Webslon\Bundle\AuthBundle\Security\Token\OAuthPreAuthenticatedToken;

/**
 * Class SwaggerUIController
 */
final class SwaggerUIController extends Controller
{
    use DocumentationControllerTrait;

    private $apiDocGenerator;
    private $twig;

    public function __construct(ApiDocGenerator $apiDocGenerator, \Twig_Environment $twig)
    {
        $this->apiDocGenerator = $apiDocGenerator;
        $this->twig = $twig;
    }

    public function __invoke(Request $request)
    {
        $api = $this->apiDocGenerator->generate();

        $baseURL = $api->getBasePath();
        if (!$baseURL) {
            $baseURL = $request->getBaseUrl();
        }

        $schema = $request->isSecure() ? 'https' : 'http';
        if ($request->getPort() === 443) {
            $schema = 'https';
        }

        $api = $this->preparePaths($baseURL, $api);

        $specArrayForTemplate = $api->toArray();
        if ('' !== $request->getBaseUrl()) {
            $specArrayForTemplate['basePath'] = $request->getBaseUrl();
        }

        $clientId = getenv('MICROSERVICE_CLIENT_ID');
        $clientSecret = getenv('MICROSERVICE_CLIENT_SECRET');

        return $this->render('@AnbooApi/SwaggerUi/index.html.twig', [
            'swagger_data' => ['spec' => $specArrayForTemplate],
            'schema' => $schema,
            'clientSecret' => $clientSecret,
            'clientId' => $clientId,
        ]);
    }
}
