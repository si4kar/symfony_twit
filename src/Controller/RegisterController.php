<?php
/**
 * Created by PhpStorm.
 * User: Sich
 * Date: 8/7/2018
 * Time: 12:31 PM
 */

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class RegisterController extends Controller
{
    /**
     * @Route("/register", name="user_register")
     */
    public function registerAction(UserPasswordEncoderInterface $passwordEncoder, Request $request)
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $password = $passwordEncoder->encodePassword(
                $user, $user->getPlainPassword()
            );

            $user->setPassword($password);
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            $this->redirect('micro_post_index');
        }

        return $this->render('register/register.html.twig', [
            'form' => $form->createView()
        ]);

    }
}