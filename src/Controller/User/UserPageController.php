<?php 

namespace App\Controller\User;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserPageController extends AbstractController {

	#[Route('/user/404', name: "/user/404", methods: ['GET'])]
	public function display404(): Response {

		// La fonction render renvoie automatiquement une erreur 200.
		// On ne peut pas paramétrer render pour avoir une erreur 404.
		
		// On créé le HTML issu du twig
		$html = $this->renderView('user/404.html.twig');

		// On retourne une réponse 404 avec le HTML
		return new Response($html, 404);
	}

    #[Route('/user/dashboard', name: "dashboard", methods: ['GET'])]
	public function displayDashboard(): Response {

        return $this->render('user/dashboard.html.twig');
	}

}

?>