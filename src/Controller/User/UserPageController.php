<?php 

namespace App\Controller\User;

use Exception;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Intl\Countries; // permet de récuéprer les codes pays ISO et leurs noms
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

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

	#[Route('/user/profile', name: 'user_profile')]
	public function editProfile(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
	{
		/** @var User $user */
		$user = $this->getUser();

		if (!$user) {
			throw $this->createAccessDeniedException();
		}

		$countries = Countries::getNames('en');

		if ($request->isMethod('POST')) {
			$user->setName($request->request->get('name'));
			$user->setSurname($request->request->get('surname'));
			$user->setCountry($request->request->get('country'));
			$user->setCity($request->request->get('city'));
			$user->setPhoneNumber($request->request->get('phoneNumber'));
			$user->setDescription($request->request->get('description'));

			// Gestion upload photo
			$photoFile = $request->files->get('picture-profile');
			if ($photoFile) {
				$filename = $slugger->slug(pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME));
				$filename = $filename . '-' . uniqid() . '.' . $photoFile->guessExtension();
				$photoFile->move($this->getParameter('user_pictures_directory'), $filename);

				// Supprime l'ancienne si elle existe
				if ($user->getPicturePath()) {
					@unlink($this->getParameter('user_pictures_directory').'/'.$user->getPicturePath());
				}
				$user->setPicturePath($filename);
			}

			$em->flush();

			$this->addFlash('success', 'Profile updated!');
			return $this->redirectToRoute('user_profile');
		}

		return $this->render('user/profile.html.twig', [
			'countries' => $countries,
		]);
	}


}

?>