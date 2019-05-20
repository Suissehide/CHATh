<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\UtilisateurType;
use App\Repository\UtilisateurRepository;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/")
 */
class UtilisateurController extends AbstractController
{
    /**
     * @Route("/", name="default", methods="GET")
     */
    function default(): Response {
        return $this->redirectToRoute('login');
    }

    /**
     * @Route("/login", name="login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('utilisateur/login.html.twig', [
            'controller_name' => 'LoginController',
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    /**
     * @Route("/logout", name="logout", methods="GET")
     */
    public function logout(): Response
    {
        return $this->redirectToRoute('login');
    }

    /**
     * @Route("/register", name="register", methods="GET|POST")
     */
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder, UtilisateurRepository $UtilisateurRepository): Response
    {
        $session = $request->getSession();
        $user = new Utilisateur();
        $form = $this->createForm(UtilisateurType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('validation')->isClicked()) {
                $password = $passwordEncoder->encodePassword($user, $user->getPlainPassword());
                $user->setPassword($password);
                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();

                $session->getFlashBag()->add('success', 'Félicitations ! Votre compte a été créé avec succès !');
                return $this->redirectToRoute('login');
            }
        }
        return $this->render('utilisateur/register.html.twig', [
            'controller_name' => 'RegisterController',
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/guest", name="guest")
     */
    public function guest()
    {
        return $this->render('utilisateur/guest.html.twig', [
            'controller_name' => 'GuestController',
        ]);
    }


    /**
     * @Route("/utilisateur/ajax", name="utilisateur_roles_edit")
     */
    public function roles_edit(UtilisateurRepository $utilisateurRepository, Request $request): JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        if ($request->isXmlHttpRequest()) {
            $email = $request->request->get('email');
            $roles = $request->request->get('roles');

            $utilisateur = $utilisateurRepository->findOneBy(['email' => $email]);
            if ($roles == "Invité")
                $utilisateur->setRoles(["ROLE_GUEST"]);
            else if ($roles == "Utilisateur")
                $utilisateur->setRoles(["ROLE_USER"]);
            else if ($roles == "Administrateur")
                $utilisateur->setRoles(["ROLE_ADMIN"]);
            $em->flush();
            return new JsonResponse();
        }
    }

    /**
     * @Route("/utilisateur/list", name="utilisateur_list")
     */
    public function list(UtilisateurRepository $utilisateurRepository, Request $request): Response
    {
        if ($request->isXmlHttpRequest()) {
            $current = $request->request->get('current');
            $rowCount = $request->request->get('rowCount');
            $searchPhrase = $request->request->get('searchPhrase');
            $sort = $request->request->get('sort');

            $utilisateurs = $utilisateurRepository->findByFilter($sort, $searchPhrase);
            if ($searchPhrase != "") {
                $count = count($utilisateurs->getQuery()->getResult());
            } else {
                $count = $utilisateurRepository->compte();
            }
            if ($rowCount != -1) {
                $min = ($current - 1) * $rowCount;
                $max = $rowCount;
                $utilisateurs->setMaxResults($max)->setFirstResult($min);
            }
            $utilisateurs = $utilisateurs->getQuery()->getResult();
            $rows = array();
            foreach ($utilisateurs as $utilisateur) {
                $row = array(
                    "id" => $utilisateur->getId(),
                    "nom" => $utilisateur->getNom(),
                    "prenom" => $utilisateur->getPrenom(),
                    "email" => $utilisateur->getEmail(),
                    "roles" => $utilisateur->getRoles(),
                );
                array_push($rows, $row);
            }

            $data = array(
                "current" => intval($current),
                "rowCount" => intval($rowCount),
                "rows" => $rows,
                "total" => intval($count)
            );
            return new JsonResponse($data);
        }

        return $this->render('utilisateur/list.html.twig', [
            'controller_name' => 'ListController',
        ]);
    }

    /**
     * @Route("/utilisateur/edit", name="utilisateur_edit")
     */
    public function edit(Request $request): Response
    {
        $user = $this->getUser();

        return $this->render('utilisateur/edit.html.twig', [
            'controller_name' => 'EditController',
            'user' => $user,
        ]);
    }

    /*
    public function accountInfo()
    {
        // allow any authenticated user - we don't care if they just
        // logged in, or are logged in via a remember me cookie
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

    }

    public function resetPassword()
    {
        // require the user to log in during *this* session
        // if they were only logged in via a remember me cookie, they
        // will be redirected to the login page
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

    }
    */

}