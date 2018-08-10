<?php

namespace App\Controller;


use App\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController
{

    /**
     * @var \Twig_Environment
     */
    private $twig;

    public function __construct(\Twig_Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * @Route("/login", name="security_login")

     */
    public function loginAction(AuthenticationUtils $authenticationUtils)
    {
        return new Response($this->twig->render(
            'security/login.html.twig', [
                'last_username' => $authenticationUtils->getLastUsername(),
                'error' => $authenticationUtils->getLastAuthenticationError()
            ]
        ));
    }

    /**
     * @Route("/logout", name="security_logout")
     */
    public function logoutAction()
    {

    }

    /**
     * @Route("/confirm/{token}", name="security_confirm")
     */
    public function confirmAction(string $token,
                                  UserRepository $userRepository,
                                  EntityManager $entityManager)
    {
        $user = $userRepository->findOneBy([
            'conformationToken' => $token
        ]);

        if (null != $user) {
            $user->setEnable(true);
            $user->setConformationToken('');

            $entityManager->flush();
        }

        return new Response($this->twig->render('security/confirmation.html.twig', [
            'user' => $user
        ]));
    }
}