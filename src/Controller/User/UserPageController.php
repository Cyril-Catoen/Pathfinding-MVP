<?php 

namespace App\Controller\User;


use App\Entity\Adventure;
use App\Repository\AdventureRepository;

use App\Entity\AdventurePicture;
use App\Repository\AdventurePictureRepository;

use App\Entity\ContactList;
use App\Repository\ContactListRepository;
use App\Entity\SafetyContact;
use App\Repository\SafetyContactRepository;
use App\Entity\TimerAlert;
use App\Repository\TimerAlertRepository;

use App\Enum\Status;
use App\Enum\ViewAuthorization;
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
	public function displayDashboard(
		AdventureRepository $adventureRepo,
		SafetyContactRepository $contactRepo,
		EntityManagerInterface $em
	): Response {
		/** @var User|null $user */
		$user = $this->getUser();
		$isOwner = $user !== null;

		$adventure = null;

		if ($isOwner) {
			$adventure = $adventureRepo->findOneBy(['owner' => $user, 'status' => Status::Ongoing->value]);
			if (!$adventure) {
				$adventure = $adventureRepo->findOneBy(['owner' => $user], ['createdAt' => 'DESC']);
			}
		} else {
			$adventure = $adventureRepo->findOneBy(['viewAuthorization' => ViewAuthorization::Public->value], ['createdAt' => 'DESC']);
		}

		$timer = $adventure && $adventure->getTimerAlert() && $adventure->getTimerAlert()->isActive()
			? $adventure->getTimerAlert()
			: null;

		$contacts = [];
		if ($isOwner) {
			$contacts = $contactRepo->findBy(
				['user' => $user],
				['isFavorite' => 'DESC', 'createdAt' => 'ASC'],
				2
			);
		}

		return $this->render('user/dashboard.html.twig', [
			'userData' => $user,
			'isOwner' => $isOwner,
			'mainAdventure' => $adventure,
			'timerAlert' => $timer,
			'contacts' => $contacts,
		]);
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

	#[Route('/user/discover', name: "discover", methods: ['GET'])]
	public function displayDiscover(): Response {

		return $this->render('user/discover.html.twig');
	}

}

?>