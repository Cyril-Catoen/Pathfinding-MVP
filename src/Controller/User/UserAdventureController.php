<?php 

namespace App\Controller\User;

use App\Entity\Adventure;
use App\Repository\AdventureRepository;
use App\Entity\AdventurePicture;
use App\Repository\AdventurePictureRepository;
use App\Repository\AdventurePointRepository;
use App\Entity\AdventureType;
use App\Repository\AdventureTypeRepository;
use App\Entity\ContactList;
use App\Repository\ContactListRepository;
use App\Entity\TimerAlert;
use App\Repository\TimerAlertRepository;
use App\Enum\Status;
use App\Enum\ViewAuthorization;
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

		return $this->render('user/adventures/adventure.html.twig', [
			'adventure' => $adventure,
			'isOwner' => $isOwner,
			'isGuest' => $isGuest,
			'isViewer' => $isViewer,
			'anotherOngoing' => $anotherOngoing,
			'contactLists' => $isOwner ? $contactListRepository->findBy(['owner' => $user]) : [],
			'pictures' => $pictures,
			'points' => $points,
		]);

	}

	#[Route('/user/create-adventure', name: 'create-adventure', methods: ['GET', 'POST'])]
	public function displayCreateAdventure(
		Request $request, 
		EntityManagerInterface $entityManager,
		Security $security,
		AdventureTypeRepository $adventureTypeRepository,
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

			// ✅ Générer le lien partageable ici
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
				$typeEntity = $adventureTypeRepository->findOneBy(['name' => ucfirst(strtolower($typeName))]);
				if ($typeEntity) {
					$adventure->addType($typeEntity);
				}
			}

			// Live Track
			if ($data->getBoolean('statusLive')) {
				// à compléter : ajout dans une future table ou champ booléen
			}

			// Safety Alert
			if ($data->getBoolean('statusSafety')) {
				// à compléter aussi
			}

			// Timer Alert (si durée définie)
			$durationStr = $data->get('duration-input'); // format attendu : HH:MM:SS
			if ($durationStr) {
				$parts = explode(':', $durationStr);
				if (count($parts) === 3) {
					[$h, $m, $s] = array_map('intval', $parts);
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
			}

			$errors = $validator->validate($adventure);
			if (count($errors) > 0) {
				$messages = [];
				foreach ($errors as $error) {
					$messages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
				}
				// à adapter : afficher ou retourner les messages
				return new Response(implode('<br>', $messages), 400);
			}

			$entityManager->persist($adventure);
			$entityManager->flush();

			return $this->redirectToRoute('adventures-manager');
		}

		return $this->render('user/Adventures/create-adventure.html.twig');
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