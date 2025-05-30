<?php 

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminPageController extends AbstractController {

	#[Route('/admin/404', name: "/admin/404", methods: ['GET'])]
	public function display404(): Response {

		// La fonction render renvoie automatiquement une erreur 200.
		// On ne peut pas paramétrer render pour avoir une erreur 404.
		
		// On créé le HTML issu du twig
		$html = $this->renderView('admin/404.html.twig');

		// On retourne une réponse 404 avec le HTML
		return new Response($html, 404);
	}

	
    #[Route('/admin/dashboard', name: "admin_dashboard", methods: ['GET'])]
	public function displayAdminDashboard(): Response {

        return $this->render('admin/dashboard-admin.html.twig');
	}

}

?>