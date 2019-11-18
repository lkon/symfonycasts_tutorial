<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\Model\UserRegistrationFormModel;
use App\Form\UserRegistrationFormType;
use App\Security\LoginFormAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils)
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new \Exception('Will be intercepted before getting here');
    }

    /**
     * @Route("/register", name="app_register")
     */
    public function register(
        Request $request,
        UserPasswordEncoderInterface $userPasswordEncoder,
        GuardAuthenticatorHandler $guardAuthenticatorHandler,
        LoginFormAuthenticator $loginFormAuthenticator
    )
    {
        $form = $this->createForm(UserRegistrationFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /**
             * When you use a model class, the downside is that you need to do
             * a bit more work to transfer the data from our model object
             * into the entity object - or objects - that actually need it.
             * That's why these model classes are often called "data transfer
             * objects": they just hold data and help transfer it between
             * systems: the form system and our entity classes.
             */
            /** @var UserRegistrationFormModel $userModel */
            $userModel = $form->getData();

            $user = new User();

            $user
                ->setEmail($userModel->email)
                ->setPassword($userPasswordEncoder->encodePassword(
                    $user,
//                    $form['plainPassword']->getData())
                    $userModel->plainPassword
                ));

            if (true === $userModel->agreeTerms) {
                $user->agreeTerms();
            }
//            if (true === $form['agreeTerms']->getData()) {
//                $user->agreeTerms();
//            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            return $guardAuthenticatorHandler->authenticateUserAndHandleSuccess(
                $user,
                $request,
                $loginFormAuthenticator,
                'main'
            );
        }

//        if ($request->isMethod('POST')) {
//            $user = new User();
//            $user->setEmail($request->request->get('email'))
//                ->setFirstName('Mystery');
//
//            $user->setPassword($userPasswordEncoder->encodePassword(
//                $user,
//                $request->request->get('password')
//            ));
//
//            $em = $this->getDoctrine()->getManager();
//            $em->persist($user);
//            $em->flush();
//
//            return $guardAuthenticatorHandler->authenticateUserAndHandleSuccess(
//                $user,
//                $request,
//                $loginFormAuthenticator,
//                'main'
//            );
//        }

        return $this->render('security/register.html.twig', [
            'registerForm' => $form->createView(),
        ]);
    }
}
