<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * L'offre OVH Perso n'a pas de SSH : le workflow GitHub Actions appelle cette route
 * après l'envoi FTP pour jouer les migrations Doctrine sur le serveur.
 * Désactivée tant que DEPLOY_TOKEN est vide.
 */
final class DeployController
{
    public function __construct(
        private readonly KernelInterface $kernel,
        #[Autowire(env: 'DEPLOY_TOKEN')] private readonly string $token,
    ) {
    }

    #[Route('/_deploy/migrate', name: 'deploy_migrate', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        if ('' === $this->token || !hash_equals($this->token, (string) $request->headers->get('X-Deploy-Token'))) {
            throw new NotFoundHttpException();
        }

        $application = new Application($this->kernel);
        $application->setAutoExit(false);
        $output = new BufferedOutput();
        $code = $application->run(new ArrayInput([
            'command' => 'doctrine:migrations:migrate',
            '--no-interaction' => true,
            '--allow-no-migration' => true,
        ]), $output);

        return new Response($output->fetch(), 0 === $code ? 200 : 500, ['Content-Type' => 'text/plain']);
    }
}
