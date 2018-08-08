<?php
/**
 * Created by PhpStorm.
 * User: Sich
 * Date: 8/6/2018
 * Time: 4:59 PM
 */

namespace App\Controller;
use App\Entity\MicroPost;
use App\Entity\User;
use App\Repository\MicroPostRepository;
use App\Form\MicroPostType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;


/**
 * Class MicroPostController
 * @Route("/micro-post")
 */
class MicroPostController
{
    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var MicroPostRepository
     */
    private $microPostRepository;
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var FlashBagInterface
     */
    private $flashBag;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * MicroPostController constructor.
     * @param \Twig_Environment $twig
     */
    public function __construct(\Twig_Environment $twig, MicroPostRepository $microPostRepository,
                                FormFactoryInterface $formFactory, EntityManagerInterface $entityManager,
                                RouterInterface $router, FlashBagInterface $flashBag,
                                AuthorizationCheckerInterface $authorizationChecker
                                )
    {

        $this->twig = $twig;
        $this->microPostRepository = $microPostRepository;
        $this->formFactory = $formFactory;
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->flashBag = $flashBag;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @Route("/", name="micro_post_index")
     */
    public function indexAction(TokenStorageInterface $tokenStorage, UserRepository $userRepository)
    {
        $currentUser = $tokenStorage->getToken()->getUser();
        $usersToFollow = [];
        if ($currentUser instanceof User) {
            $posts = $this->microPostRepository
                ->findAllByUsers($currentUser->getFollowing());
            $usersToFollow = count($posts) === 0 ? $userRepository->findAllWithMoreThan5PostsExeptUser($currentUser) : [];
        } else {
            $posts = $this->microPostRepository->findBy(
                [],
                ['time' => 'DESC']
            );
        }


        $html = $this->twig->render('micro-post/index.html.twig',
            [
                'posts' => $posts,
                'usersToFollow' => $usersToFollow
            ]
        );

        return new Response($html);
    }

    /**
     * @Route("/add", name="micro_post_add")
     * @param Request $request
     * @param TokenStorageInterface $tokenStorage
     * @return RedirectResponse|Response
     * @Security("is_granted('ROLE_USER')")
     */
    public function addAction(Request $request, TokenStorageInterface $tokenStorage)
    {
        $user = $tokenStorage->getToken()->getUser();
        $microPost = new MicroPost();
        $microPost->setUser($user);

        $form = $this->formFactory->create(MicroPostType::class, $microPost);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->entityManager;
            $em->persist($microPost);
            $em->flush();

            return new RedirectResponse($this->router->generate('micro_post_index'));
        }
        return new Response($this->twig->render('micro-post/add.html.twig',
            ['form' => $form->createView()])
        );
    }

    /**
     * @Route("/user/{username}", name="micro_post_user")
     */
    public function userPostsAction(User $userWithPosts)
    {
        $html = $this->twig->render('micro-post/user-posts.html.twig',
           /* ['posts' => $this->microPostRepository->findBy(
                ['user' => $userWithPosts],
                ['time' => 'DESC'])]*/
           [
               'posts' => $userWithPosts->getPosts(),
               'user' => $userWithPosts,
           ]
        );

        return new Response($html);
    }

    /**
     * @Route("/edit/{id}", name="micro_post_edit")
     * @Security("is_granted('edit', microPost)", message="access denied")
     */
    public function editAction(MicroPost $microPost, Request $request)
    {

        $form = $this->formFactory->create(MicroPostType::class, $microPost);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->entityManager;

            $em->flush();

            return new RedirectResponse($this->router->generate('micro_post_index'));
        }
        return new Response($this->twig->render('micro-post/add.html.twig',
            ['form' => $form->createView()])
        );
    }

    /**
     * @Route("/delete/{id}", name="micro_post_delete" )
     * @Security("is_granted('delete', microPost)", message="access denied")
     */
    public function deleteAction(MicroPost $microPost, Request $request)
    {
        $em = $this->entityManager;
        $em->remove($microPost);
        $em->flush();

        $this->flashBag->add('notice', 'Micro post was deleted');

        return new RedirectResponse($this->router->generate('micro_post_index'));
    }

    /**
     * @Route("/{id}", name="micro_post_post")
     */
    public function postAction(MicroPost $post)
    {
        return new Response($this->twig->render('micro-post/post.html.twig',
            ['post' => $post])
        );
    }
}