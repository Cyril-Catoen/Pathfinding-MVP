<?php 

namespace App\Controller\User;

use App\Entity\Adventure;
use App\Repository\AdventureRepository;
use App\Entity\AdventureFile;
use App\Repository\AdventureFileRepository;
use App\Entity\AdventurePicture;
use App\Repository\AdventurePictureRepository;
use App\Entity\AdventureType;
use App\Repository\AdventureTypeRepository;
use App\Entity\ContactList;
use App\Repository\ContactListRepository;
use App\Entity\SafetyAlert;
use App\Repository\SafetyAlertRepository;
use App\Entity\SafetyContact;
use App\Repository\SafetyContactRepository;
use App\Entity\TimerAlert;
use App\Repository\TimerAlertRepository;
use App\Enum\Status;
use Exception;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
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

		return $this->render('user/adventures/adventure.html.twig', [
			'adventure' => $adventure,
			'isOwner' => $isOwner,
			'isGuest' => $isGuest,
			'isViewer' => $isViewer,
			'anotherOngoing' => $anotherOngoing,
			'contactLists' => $isOwner ? $contactListRepository->findBy(['owner' => $user]) : [],
			'pictures' => $pictures,
		]);

	}

	#[Route('/user/adventure/{id}/update-status', name: 'update_adventure_status', methods: ['POST'])]
	public function updateStatus(
		Request $request,
		Adventure $adventure,
		EntityManagerInterface $em,
		AdventureRepository $adventureRepository,
		Security $security
	): Response {
		$this->denyAccessUnlessGranted('EDIT', $adventure);

		$user = $security->getUser();
		$status = $request->request->get('status');
		$statusEnum = Status::from($status);

		// Si l'utilisateur veut passer en 'ongoing', vérifier qu'aucune autre aventure en cours n'existe
		if ($statusEnum === Status::Ongoing) {
			$otherOngoing = $adventureRepository->findOneBy([
				'owner' => $user,
				'status' => Status::Ongoing,
			]);

			if ($otherOngoing && $otherOngoing->getId() !== $adventure->getId()) {
				$this->addFlash('error', 'Vous avez déjà une aventure en cours.');
				return $this->redirectToRoute('adventure', ['id' => $adventure->getId()]);
			}

			// Mise à jour de la date de départ
			$adventure->setStartDate(new \DateTime());
		}

		$adventure->setStatus($statusEnum);
		$em->flush();

		return $this->redirectToRoute('adventure', ['id' => $adventure->getId()]);
	}

	#[Route('/user/adventure/{id}/update-title', name: 'update_adventure_title', methods: ['POST'])]
	public function updateTitle(Request $request, Adventure $adventure, EntityManagerInterface $em): Response {
		$this->denyAccessUnlessGranted('EDIT', $adventure);

		$title = trim($request->request->get('title', ''));
		if ($title !== '' && mb_strlen($title) <= 100) {
			$adventure->setTitle($title);
			$adventure->setUpdatedAt(new \DateTimeImmutable());
			$em->flush();
		}

		return $this->redirectToRoute('adventure', ['id' => $adventure->getId()]);
	}

	#[Route('/user/adventure/{id}/upload-photos', name: 'upload_adventure_photos', methods: ['POST'])]
	public function uploadAdventurePhotos(
		int $id,
		Request $request,
		EntityManagerInterface $em,
		AdventureRepository $adventureRepository,
		Security $security
	): Response {
		$adventure = $adventureRepository->find($id);
		if (!$adventure) {
			throw $this->createNotFoundException('Adventure not found.');
		}

		$this->denyAccessUnlessGranted('EDIT', $adventure);

		$files = $request->files->get('photos');

		if (is_array($files)) {
			foreach ($files as $file) {
				if ($file && $file->isValid()) {
					$filename = uniqid().'.'.$file->guessExtension();
					$file->move($this->getParameter('pictures_directory'), $filename);

					$picture = new \App\Entity\AdventurePicture();
					$picture->setAdventure($adventure);
					$picture->setPicturePath('uploads/pictures/'.$filename);
					$picture->setUploadedAt(new \DateTimeImmutable());

					$em->persist($picture);
				}
			}
			$em->flush();
		}

		return $this->redirectToRoute('adventure', ['id' => $id]);
	}

	#[Route('/user/adventure/{adventureId}/delete-photo/{photoId}', name: 'adventure_photo_delete', methods: ['DELETE'])]
	public function deletePicture(
		int $adventureId,
		int $photoId,
		AdventureRepository $adventureRepo,
		AdventurePictureRepository $pictureRepo,
		EntityManagerInterface $em
	): JsonResponse {
		try {
			$adventure = $adventureRepo->find($adventureId);
			$picture = $pictureRepo->find($photoId);

			if (!$adventure || !$picture || $picture->getAdventure()->getId() !== $adventureId) {
				return new JsonResponse(['error' => 'Aventure ou photo invalide.'], 404);
			}

			// Vérifie que l'utilisateur connecté est bien le propriétaire
			if ($adventure->getOwner()->getId() !== $this->getUser()->getId()) {
				return new JsonResponse(['error' => 'Non autorisé.'], 403);
			}

			// Supprime physiquement le fichier image
			$photoPath = $this->getParameter('kernel.project_dir') . '/public/' . $picture->getPicturePath();
			if (file_exists($photoPath)) {
				unlink($photoPath);
			}

			// Supprime l'entité
			$em->remove($picture);
			$em->flush();

			// Log temporaire (peut être retiré après debug)
			file_put_contents(
				$this->getParameter('kernel.project_dir') . '/var/log/photo_delete.log',
				"[" . date('Y-m-d H:i:s') . "] Photo $photoId supprimée de l'aventure $adventureId\n",
				FILE_APPEND
			);

			return new JsonResponse(['success' => true], 200, ['Content-Type' => 'application/json']);
		} catch (\Throwable $e) {
			// Log l'erreur
			file_put_contents(
				$this->getParameter('kernel.project_dir') . '/var/log/photo_delete.log',
				"[" . date('Y-m-d H:i:s') . "] Erreur suppression photo $photoId : " . $e->getMessage() . "\n",
				FILE_APPEND
			);

			return new JsonResponse([
				'error' => 'Erreur serveur : ' . $e->getMessage()
			], 500);
		}
	}

	#[Route('/user/adventure/{id}/update-description', name: 'update_adventure_description', methods: ['POST'])]
	public function updateDescription(Request $request, Adventure $adventure, EntityManagerInterface $em): Response {
		$this->denyAccessUnlessGranted('EDIT', $adventure);

		$description = trim($request->request->get('description'));
		if ($description === 'No description') {
			$description = null;
		}

		$adventure->setDescription($description);
		$em->flush();

		return $this->redirectToRoute('adventure', ['id' => $adventure->getId()]);
	}



	#[Route('/user/adventure/{id}/update', name: 'update_adventure', methods: ['POST'])]
	public function updateAdventure(Request $request, Adventure $adventure, EntityManagerInterface $em): Response {
		$this->denyAccessUnlessGranted('EDIT', $adventure);

		// Exemples de champs
		$adventure->setStartDate(new \DateTime($request->request->get('start_date')));
		$adventure->setEndDate(new \DateTime($request->request->get('end_date')));
		$adventure->setViewAuthorization($request->request->get('visibility'));

		$em->flush();

		return $this->redirectToRoute('adventure', ['id' => $adventure->getId()]);
	}

	#[Route('/user/adventure/{id}/update-alert-settings', name: 'update_alert_settings', methods: ['POST'])]
	public function updateAlertSettings(Request $request, Adventure $adventure, EntityManagerInterface $em, TimerAlertRepository $timerRepo): Response {
		$this->denyAccessUnlessGranted('EDIT', $adventure);

		$enabled = $request->request->getBoolean('safetyEnabled');
		$hours = (int)$request->request->get('hours', 0);
		$minutes = (int)$request->request->get('minutes', 0);
		$seconds = (int)$request->request->get('seconds', 0);

		if ($enabled) {
			$alertTime = (new \DateTime())->add(new \DateInterval("PT{$hours}H{$minutes}M{$seconds}S"));
			$timer = $adventure->getTimerAlert() ?? new TimerAlert();
			$timer->setAdventure($adventure);
			$timer->setAlertTime($alertTime);
			$timer->setIsActive(true);
			$em->persist($timer);
		} else {
			if ($adventure->getTimerAlert()) {
				$em->remove($adventure->getTimerAlert());
			}
		}

		$em->flush();

		return $this->redirectToRoute('adventure', ['id' => $adventure->getId()]);
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
			$adventure->setStartDate(new \DateTime($data->get('start_date')));
			$adventure->setEndDate(new \DateTime($data->get('end_date')));
			$adventure->setStatus(\App\Enum\Status::Preparation);

			// ✅ Générer le lien partageable ici
			$adventure->setShareLink(bin2hex(random_bytes(16)));

			// Visibilité
			$visibility = $data->get('visibility');
			$viewAuth = match($visibility) {
				'privacy1' => \App\Enum\ViewAuthorization::Private,
				'privacy2' => \App\Enum\ViewAuthorization::Public,
				'privacy3' => \App\Enum\ViewAuthorization::Selection,
				default    => \App\Enum\ViewAuthorization::Private,
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