<?php


namespace App\Controller\Guest;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class GuestLoginController extends AbstractController {


	#[Route('/login', name: "login", methods: ['GET'])]
	public function displayLogin(AuthenticationUtils $authenticationUtils): Response {

		$error = $authenticationUtils->getLastAuthenticationError();

		return $this->render('guest/login.html.twig', [
			'error' => $error
		]);

	}

	#[Route('/logout', name: "logout", methods: ['GET'])]
	public function logout() {

	}

    #[Route('/register', name: "register", methods: ['GET', 'POST'])]
	public function displayRegister(AuthenticationUtils $authenticationUtils): Response {

		$error = $authenticationUtils->getLastAuthenticationError();

		return $this->render('guest/register.html.twig');

	}
}

?>