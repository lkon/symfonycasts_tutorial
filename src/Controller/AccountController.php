<?php

namespace App\Controller;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AccountController
 * @package App\Controller
 * @IsGranted("ROLE_USER")
 */
class AccountController extends BaseController
{
    /**
     * @Route("/account", name="app_account")
     */
    public function index(LoggerInterface $logger)
    {
        /** @var User $user */
        $user = $this->getUser();
        $logger->debug('Checking account page for' . $user->getEmail());

        return $this->render('account/index.html.twig', [
            'controller_name' => 'AccountController',
        ]);
    }

    /**
     * This new endpoint will return the JSON representation of whoever is logged in.
     * @Route("/api/account", name="api_account")
     */
    public function accountApi()
    {
        // We can safely do this thanks to the annotation on the class:
        // every method requires authentication.
        /** @var User $user */
        $user = $this->getUser();

        // we just need to tell the json() method to only
        // serialize properties that are in the group called "main".
        return $this->json($user, 200, [], [
            'groups' => ['main'],
        ]);
    }
}
