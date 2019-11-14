<?php

namespace App\Controller\Auth;

use App\Entity\User;
use App\Form\RegisterFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;

class RegisterController extends Controller
{
    public function register(TokenGeneratorInterface $tokenGenerator,
                             Request $request, UserPasswordEncoderInterface $passwordEncoder, TranslatorInterface $translator)
    {
        $path = $request->get('_target_path') ?: "";
        if ($this->getUser()) {
            return $this->redirect("");
        }

        $user = new User();
        $form = $this->createForm(RegisterFormType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $password = $passwordEncoder->encodePassword($user, $user->getPlainPassword());
            $user->setPassword($password);

            $user->setEmailConfirmed(false);
            $user->setEmailConfirmationToken($tokenGenerator->generateToken());
//            $emailConfirmationController->setContainer($this->container);
//            $emailConfirmationController->sendEmailConfirmMail($request, $user);

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

//            $actionLogger->logUser($user, "register", [
//                'source' => 'form',
//            ]);

            $request->getSession()
                ->getFlashBag()
                ->add('success', $translator->trans('auth.registerSuccess', [], 'auth'));
            return $this->redirectToRoute('auth_login', ['_target_path' => $path]);
        }

        return $this->render(
            'auth/register.html.twig',
            array('form' => $form->createView())
        );
    }
}
