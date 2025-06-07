<?php 

namespace App\Controller\User;

use App\Entity\Adventure;
use App\Repository\AdventureRepository;
use App\Entity\AdventurePicture;
use App\Repository\AdventurePictureRepository;
use App\Repository\AdventurePointRepository;
use App\Entity\AdventureType;
use App\Repository\AdventureTypeRepository;
use App\Service\AdventureTypeIconHelper;
use App\Entity\ContactList;
use App\Repository\ContactListRepository;
use App\Entity\TimerAlert;
use App\Repository\TimerAlertRepository;
use App\Enum\Status;
use App\Enum\ViewAuthorization;
use App\Enum\AdventureTypeList;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class UserAdventureController extends AbstractController {

    #[Route('/user/adventures-manager', name: 'adventures-manager', methods: ['GET'])]
    public function displayUserAdventures(
		AdventureRepository $adventureRepository, 
		Security $security
		): Response {
		/** @var User $user */
		$user = $security->getUser();

		// Récupère toutes les aventures de l'utilisateur
		$allAdventures = $adventureRepository->findBy(['owner' => $user]);

		// Sépare l'aventure en cours
		$ongoing = null;
		$others = [];

		foreach ($allAdventures as $adventure) {
			if ($adventure->getStatus() === Status::Ongoing) {
				$ongoing = $adventure;
			} else {
				$others[] = $adventure;
			}
		}

		// Tri des autres aventures (date début DESC)
		usort($others, fn($a, $b) => $b->getStartDate() <=> $a->getStartDate());

		return $this->render('user/Adventures/adventures-manager.html.twig', [
			'ongoingAdventure' => $ongoing,
			'otherAdventures' => $others,
		]);
	}
	

    #[Route('/user/adventure/{id}', name: 'adventure', methods: ['GET', 'POST'])]
	public function showAdventure(
		Adventure $adventure,
		AdventureRepository $adventureRepository,
		AdventurePictureRepository $pictureRepository,
		AdventurePointRepository $adventurePointRepository,
		ContactListRepository $contactListRepository,
		Security $security
	): Response {
		$user = $security->getUser();

		$isOwner = $user && $adventure->getOwner() === $user;
		$isGuest = !$user && $adventure->getViewAuthorization() !== 'private';
		$isViewer = $user && !$isOwner;

		$anotherOngoing = false;

		if ($isOwner) {
			$otherOngoing = $adventureRepository->findOneBy([
				'owner' => $user,
				'status' => Status::Ongoing,
			]);

			// Exclut l’aventure actuelle du check
			$anotherOngoing = $otherOngoing && $otherOngoing->getId() !== $adventure->getId();
		}

		if (!$isOwner && $adventure->getViewAuthorization() === 'private') {
			throw $this->createAccessDeniedException('This adventure is private.');
		}

		  // 🖼️ Récupération des images liées à l'aventure
		$pictures = $pictureRepository->findBy(
			['adventure' => $adventure],
			['position' => 'ASC', 'uploadedAt' => 'ASC']
		);

		$points = $adventurePointRepository->findBy(['adventure' => $adventure], ['recordedAt' => 'ASC']);
		// Valeur par défaut si pas de timer ou de endDate : 24:00:00
		$timerDuration = '24:00:00';

		$timerAlert = $adventure->getTimerAlert();
		$endDate = $adventure->getEndDate();

		if ($timerAlert && $endDate) {
			$alertTime = $timerAlert->getAlertTime();
			if ($alertTime) {
				$diff = $endDate->diff($alertTime);
				// Attention, $diff->invert = 1 si endDate > alertTime (donc résultat négatif)
				// Pour l'UX, on affiche 00:00:00 si négatif
				if ($diff->invert) {
					$timerDuration = '00:00:00';
				} else {
					$totalHours = $diff->h + ($diff->days * 24);
					$timerDuration = sprintf('%02d:%02d:%02d', $totalHours, $diff->i, $diff->s);
				}
			}
		}

		return $this->render('user/adventures/adventure.html.twig', [
			'adventure' => $adventure,
			'isOwner' => $isOwner,
			'isGuest' => $isGuest,
			'isViewer' => $isViewer,
			'anotherOngoing' => $anotherOngoing,
			'contactLists' => $isOwner ? $contactListRepository->findBy(['owner' => $user]) : [],
			'pictures' => $pictures,
			'points' => $points,
			'timerDuration' => $timerDuration,
			'types' => $adventure->getTypes(), // liste des types sélectionnés par l'user à la création
			'adventureTypes' => AdventureTypeList::cases(), // afficher la liste complète pour édition
			'typeIcons' => AdventureTypeIconHelper::getIconMap(),
		]);

	}

	#[Route('/user/create-adventure', name: 'create-adventure', methods: ['GET', 'POST'])]
	public function displayCreateAdventure(
		Request $request, 
		EntityManagerInterface $entityManager,
		Security $security,
		AdventureTypeRepository $adventureTypeRepository,
		ContactListRepository $contactListRepository,
		ValidatorInterface $validator
	): Response {
		if ($request->isMethod('POST')) {
			$user = $security->getUser();
			$data = $request->request;

			$adventure = new Adventure();
			$adventure->setOwner($user);
			$adventure->setTitle($data->get('title'));
			$adventure->setStartDate(new \DateTimeImmutable($data->get('start_date')));
			$adventure->setEndDate(new \DateTimeImmutable($data->get('end_date')));
			$adventure->setStatus(Status::Preparation);

			// Générer le lien partageable
			$adventure->setShareLink(bin2hex(random_bytes(16)));

			// Visibilité
			$visibility = $data->get('visibility');
			$viewAuth = match($visibility) {
				'privacy1' => ViewAuthorization::Private,
				'privacy2' => ViewAuthorization::Public,
				'privacy3' => ViewAuthorization::Selection,
				default    => ViewAuthorization::Private,
			};
			$adventure->setViewAuthorization($viewAuth);

			// Types d'aventure
			$adventureTypes = $data->all('adventures');
			foreach ($adventureTypes as $typeName) {
				$typeEntity = $adventureTypeRepository->findOneBy(['name' => $typeName]);
				if ($typeEntity) {
					$adventure->addType($typeEntity);
				}
			}

			// Live Track (à compléter)
			if ($data->getBoolean('statusLive')) {
				// ...
			}

			// Safety Alert bloc
			if ($data->getBoolean('statusSafety')) {
				// TIMER ALERT
				$durationStr = $data->get('duration-input') ?: '24:00:00';
				$parts = explode(':', $durationStr);

				if (count($parts) === 3) {
					[$h, $m, $s] = array_map('intval', $parts);

					// Sécurité : bloque la valeur max à 72h00:00
					if ($h > 72) $h = 72;
					if ($m > 59) $m = 59;
					if ($s > 59) $s = 59;

					// Si tout est à zéro, refuse (ou force 24h)
					if ($h === 0 && $m === 0 && $s === 0) {
						$h = 24; $m = 0; $s = 0;
					}

					$interval = new \DateInterval("PT{$h}H{$m}M{$s}S");
					$alertTime = (new \DateTimeImmutable($data->get('end_date')))->add($interval);

					$timerAlert = new TimerAlert();
					$timerAlert->setAdventure($adventure);
					$timerAlert->setAlertTime($alertTime);
					$timerAlert->setIsActive(true);
					$timerAlert->setUpdatedByUser($user);
					$timerAlert->setUpdatedAt(new \DateTimeImmutable());
					$entityManager->persist($timerAlert);
				}

				// CONTACT LIST
				$contactListId = $data->get('contact_list');
				if ($contactListId) {
					$contactList = $contactListRepository->find($contactListId);
					if ($contactList) {
						$adventure->setContactList($contactList); // association (à adapter selon ton modèle)
						// Tu peux aussi associer à $timerAlert si tu préfères.
					}
				}
			}

			$errors = $validator->validate($adventure);
			if (count($errors) > 0) {
				$messages = [];
				foreach ($errors as $error) {
					$messages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
				}
				return new Response(implode('<br>', $messages), 400);
			}

			$entityManager->persist($adventure);
			$entityManager->flush();

			return $this->redirectToRoute('adventures-manager');
		}

		// ... Récupère les contactLists de l'utilisateur
		$contactLists = $contactListRepository->findBy(['owner' => $security->getUser()]);

		return $this->render('user/Adventures/create-adventure.html.twig', [
			'contactLists' => $contactLists
		]);
	}



    #[Route('/user/delete-adventure/{id}', name: 'delete-adventure', methods: ['GET', 'POST'])]
	public function displayDeleteAdventure(
		int $id,
		EntityManagerInterface $entityManager,
		AdventureRepository $adventureRepository,
		Security $security
	): Response {
		/** @var User $user */
		$user = $security->getUser();

		$adventure = $adventureRepository->find($id);

		if (!$adventure) {
			$this->addFlash('error', 'Adventure not found.');
			return $this->redirectToRoute('adventures-manager');
		}

		// Vérifie que l'utilisateur est bien le propriétaire
		if ($adventure->getOwner() !== $user) {
			$this->addFlash('error', 'You cannot delete an adventure which is not yours.');
			return $this->redirectToRoute('adventures-manager');
		}

		$entityManager->remove($adventure);
		$entityManager->flush();

		$this->addFlash('success', 'Adventure successfully removed.');

		return $this->redirectToRoute('adventures-manager');
	}


	#[Route('/share/{shareLink}', name: 'adventure_share')]
	public function share(string $shareLink, AdventureRepository $repo): Response {
		$adventure = $repo->findOneBy(['shareLink' => $shareLink]);

		if (!$adventure || !$adventure->isVisibleTo(null)) {
			throw $this->createNotFoundException();
		}

		return $this->render('adventure/public_view.html.twig', [
			'adventure' => $adventure
		]);
}

    
}