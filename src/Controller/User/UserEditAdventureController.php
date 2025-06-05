<?php 

namespace App\Controller\User;

use Psr\Log\LoggerInterface;

use App\Entity\Adventure;
use App\Repository\AdventureRepository;
use App\Entity\AdventureFile;
use App\Repository\AdventureFileRepository;
use App\Entity\AdventurePicture;
use App\Repository\AdventurePictureRepository;
use App\Entity\AdventurePoint;
use App\Repository\AdventurePointRepository;
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
use App\Enum\AdventureFileType;
use App\Enum\Status;
use App\Enum\ViewAuthorization;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class UserEditAdventureController extends AbstractController {
    
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
			$adventure->setStartDate(new \DateTimeImmutable());
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



	// #[Route('/user/adventure/{id}/update', name: 'update_adventure', methods: ['POST'])]
	// public function updateAdventure(Request $request, Adventure $adventure, EntityManagerInterface $em): Response {
	// 	$this->denyAccessUnlessGranted('EDIT', $adventure);

	// 	// Exemples de champs
	// 	$adventure->setStartDate(new \DateTime($request->request->get('start_date')));
	// 	$adventure->setEndDate(new \DateTime($request->request->get('end_date')));
	// 	$adventure->setViewAuthorization($request->request->get('visibility'));

	// 	$em->flush();

	// 	return $this->redirectToRoute('adventure', ['id' => $adventure->getId()]);
	// }

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


	//Gestion des dates en AJAX
	#[Route('/user/adventure/{id}/update-dates', name: 'update_adventure_dates', methods: ['POST'])]
	public function updateDates(
		int $id,
		Request $request,
		AdventureRepository $adventureRepository,
		EntityManagerInterface $em,
		Security $security
	): JsonResponse {
		$adventure = $adventureRepository->find($id);
		if (!$adventure) {
			return new JsonResponse(['success' => false, 'error' => 'Adventure not found'], 404);
		}
		$user = $security->getUser();
		if (!$user || $adventure->getOwner() !== $user) {
			return new JsonResponse(['success' => false, 'error' => 'Not allowed'], 403);
		}

		$start = $request->request->get('start_date');
		$end = $request->request->get('end_date');

		try {
			$startDate = $start ? new \DateTimeImmutable($start) : null;
			$endDate = $end ? new \DateTimeImmutable($end) : null;
		} catch (\Exception $e) {
			return new JsonResponse(['success' => false, 'error' => 'Invalid date format']);
		}

		if ($startDate && $endDate && $startDate > $endDate) {
			return new JsonResponse([
				'success' => false,
				'error' => "La date de début doit précéder la date de fin."
			]);
		}

		if ($startDate) $adventure->setStartDate($startDate);
		if ($endDate) $adventure->setEndDate($endDate);
		$em->flush();

		return new JsonResponse([
			'success' => true,
			'start_date' => $adventure->getStartDate()?->format('Y-m-d H:i'),
			'end_date' => $adventure->getEndDate()?->format('Y-m-d H:i'),
		]);
	}


    // Ajout de fichiers pour une aventure
	#[Route('/user/adventure/{id}/upload-file', name: 'upload_adventure_file', methods: ['POST'])]
	public function uploadAdventureFile(
		int $id,
		Request $request,
		AdventureRepository $adventureRepository,
		AdventureFileRepository $adventureFileRepository,
		EntityManagerInterface $em,
		Security $security,
		LoggerInterface $logger
	): JsonResponse {
		try {
			$adventure = $adventureRepository->find($id);
			if (!$adventure) {
				return new JsonResponse(['error' => 'Adventure not found.'], 404);
			}
			$this->denyAccessUnlessGranted('EDIT', $adventure);

			/** @var UploadedFile|null $uploadedFile */
			$uploadedFile = $request->files->get('file');
			$type = $request->request->get('type');

			if (!$type || !AdventureFileType::tryFrom($type)) {
				return new JsonResponse(['error' => 'Invalid file type.'], 400);
			}

			if (!$uploadedFile || !$uploadedFile->isValid()) {
				return new JsonResponse(['error' => 'No valid file.'], 400);
			}

			$allowedMimeTypes = [
				'application/pdf',
				'application/x-pdf',
				'application/msword',
				'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
				'application/vnd.oasis.opendocument.text',
				'text/plain',
				'text/csv',
				'application/vnd.ms-excel',
				'application/json',
			];
			$maxFileSize = 20 * 1024 * 1024; // 20 Mo
			$maxTotalSize = 50 * 1024 * 1024; // 50 Mo
			$maxFiles = 5;

			$existingFiles = $adventureFileRepository->findBy(['adventure' => $adventure]);
			if (count($existingFiles) >= $maxFiles) {
				return new JsonResponse(['error' => 'Limite de 5 fichiers atteinte.'], 400);
			}

			$totalSize = array_sum(array_map(fn($f) => $f->getSize(), $existingFiles));
			if (($totalSize + $uploadedFile->getSize()) > $maxTotalSize) {
				return new JsonResponse(['error' => 'Limite totale de 50 Mo dépassée.'], 400);
			}
			if ($uploadedFile->getSize() > $maxFileSize) {
				return new JsonResponse(['error' => 'Fichier trop volumineux (20 Mo max).'], 400);
			}

			// Vérification MIME type du fichier temp (optionnel, car non fiable à 100% sur tous OS)
			$fileMime = $uploadedFile->getMimeType();
			if (!in_array($fileMime, $allowedMimeTypes)) {
				return new JsonResponse(['error' => 'Format non autorisé. (MIME: ' . $fileMime . ')'], 400);
			}

			// === Déplacement immédiat du fichier temp dans le répertoire cible ===
			$uploadDir = $this->getParameter('adventure_files_directory');
			if (!is_dir($uploadDir)) {
				mkdir($uploadDir, 0777, true);
			}
			$originalName = $uploadedFile->getClientOriginalName();
			$fileExt = $uploadedFile->getClientOriginalExtension();
			$uniqueName = uniqid('advf_').'.'.$fileExt;
			$uploadedFile->move($uploadDir, $uniqueName);

			// === Re-vérification (plus sûre) sur le fichier final ===
			$targetPath = $uploadDir . DIRECTORY_SEPARATOR . $uniqueName;
			$finalMime = mime_content_type($targetPath);
			$finalSize = filesize($targetPath);

			if (!in_array($finalMime, $allowedMimeTypes)) {
				// Optionnel : supprimer si mime non autorisé
				@unlink($targetPath);
				return new JsonResponse(['error' => 'Format non autorisé après upload. (MIME: ' . $finalMime . ')'], 400);
			}
			if ($finalSize > $maxFileSize) {
				@unlink($targetPath);
				return new JsonResponse(['error' => 'Fichier trop volumineux (20 Mo max) après upload.'], 400);
			}

			// === Création de l'entité AdventureFile ===
			$adventureFile = new AdventureFile();
			$adventureFile->setAdventure($adventure)
				->setFileName($originalName)
				->setType(AdventureFileType::from($type))
				->setViewAuthorization($adventure->getViewAuthorization())
				->setIsExternal(false)
				->setMimeType($finalMime)
				->setFileExtension($fileExt)
				->setSize($finalSize)
				->setUploadedAt(new \DateTimeImmutable());
			$adventureFile->setExternalUrl('uploads/adventure_files/' . $uniqueName);
			$em->persist($adventureFile);
			$em->flush();

			$logger->info('Fichier adventure uploadé', [
				'adventure_id' => $adventure->getId(),
				'name' => $originalName,
				'mime' => $finalMime,
				'size' => $finalSize,
			]);

			return new JsonResponse([
				'success' => true,
				'file' => [
					'id' => $adventureFile->getId(),
					'name' => $originalName,
					'type' => $adventureFile->getType()->value,
					'typeLabel' => $adventureFile->getType()->label(),
					'typeIcon' => $adventureFile->getType()->icon(),
					'size' => $adventureFile->getSize(),
					'url' => $this->generateUrl('download_adventure_file', ['id' => $adventureFile->getId()]),
				]
			]);
		} catch (\Throwable $e) {
			$logger->error('Erreur lors de l\'upload adventure file', [
				'msg' => $e->getMessage(),
				'trace' => $e->getTraceAsString(),
			]);
			return new JsonResponse(['error' => 'Erreur serveur : ' . $e->getMessage()], 500);
		}
	}



    // Suppression d'un fichier
    #[Route('/user/adventure/{adventureId}/delete-file/{fileId}', name: 'delete_adventure_file', methods: ['DELETE'])]
    public function deleteAdventureFile(
        int $adventureId,
        int $fileId,
        AdventureRepository $adventureRepo,
        AdventureFileRepository $fileRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $adventure = $adventureRepo->find($adventureId);
        $file = $fileRepo->find($fileId);
        if (!$adventure || !$file || $file->getAdventure()->getId() !== $adventureId) {
            return new JsonResponse(['error' => 'Invalid adventure or file.'], 404);
        }
        if ($adventure->getOwner()->getId() !== $this->getUser()->getId()) {
            return new JsonResponse(['error' => 'Non autorisé.'], 403);
        }
        // Remove file
        $filePath = $this->getParameter('kernel.project_dir') . '/public/' . $file->getExternalUrl();
        if (file_exists($filePath)) unlink($filePath);
        $em->remove($file);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    // Route de download sécurisé (GET, peut être protégée par droits si besoin)
    #[Route('/user/adventure/download-file/{id}', name: 'download_adventure_file', methods: ['GET'])]
    public function downloadAdventureFile(AdventureFile $file): Response {
        // Vérifier les droits ici si tu veux (ex: owner ou autorisé)
        $filePath = $this->getParameter('kernel.project_dir') . '/public/' . $file->getExternalUrl();
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException();
        }
        return $this->file($filePath, $file->getFileName());
    }

	#[Route('/user/adventure/{id}/upload-track', name: 'upload_adventure_track', methods: ['POST'])]
	public function uploadTrack(
		int $id,
		Request $request,
		AdventureRepository $adventureRepository,
		AdventurePointRepository $adventurePointRepository,
		EntityManagerInterface $em
	): JsonResponse {
		$adventure = $adventureRepository->find($id);
		if (!$adventure) {
			return new JsonResponse(['error' => 'Adventure not found.'], 404);
		}
		$this->denyAccessUnlessGranted('EDIT', $adventure);

		/** @var UploadedFile|null $file */
		$file = $request->files->get('track');
		if (!$file || !$file->isValid()) {
			return new JsonResponse(['error' => 'No valid file.'], 400);
		}

		$ext = strtolower($file->getClientOriginalExtension());
		if (!in_array($ext, ['gpx', 'csv', 'json'])) {
			return new JsonResponse(['error' => 'Format non supporté (gpx, csv, json seulement)'], 400);
		}

		$points = [];
		// GPX
		if ($ext === 'gpx') {
			$xml = simplexml_load_file($file->getPathname());
			foreach ($xml->trk->trkseg->trkpt as $pt) {
				$points[] = [
					'lat' => (float)$pt['lat'],
					'lng' => (float)$pt['lon'],
					'ele' => isset($pt->ele) ? (float)$pt->ele : null,
					'time' => isset($pt->time) ? new \DateTimeImmutable((string)$pt->time) : new \DateTimeImmutable(),
				];
			}
		}
		// CSV (lat,lon,ele)
		elseif ($ext === 'csv') {
			$rows = array_map('str_getcsv', file($file->getPathname()));
			foreach ($rows as $row) {
				if (count($row) >= 2) {
					$points[] = [
						'lat' => (float)$row[0],
						'lng' => (float)$row[1],
						'ele' => isset($row[2]) ? (float)$row[2] : null,
						'time' => new \DateTimeImmutable(),
					];
				}
			}
		}
		// JSON (array de {lat, lon, ele})
		elseif ($ext === 'json') {
			$data = json_decode(file_get_contents($file->getPathname()), true);
			foreach ($data as $row) {
				$points[] = [
					'lat' => (float)($row['lat'] ?? $row['latitude']),
					'lng' => (float)($row['lon'] ?? $row['lng'] ?? $row['longitude']),
					'ele' => $row['ele'] ?? $row['elevation'] ?? null,
					'time' => new \DateTimeImmutable(),
				];
			}
		}

		// Nettoyage des anciens points
		foreach ($adventure->getAdventurePoints() as $pt) {
			$em->remove($pt);
		}
		// Ajout des nouveaux points
		foreach ($points as $pt) {
			$adventurePoint = new AdventurePoint();
			$adventurePoint->setAdventure($adventure)
				->setLatitude($pt['lat'])
				->setLongitude($pt['lng'])
				->setElevation($pt['ele'])
				->setRecordedAt($pt['time']);
			$em->persist($adventurePoint);
		}
		$em->flush();

		return new JsonResponse(['success' => true, 'count' => count($points)]);
	}

	#[Route('/user/adventure/{id}/points', name: 'adventure_points', methods: ['GET'])]
	public function getPoints(
		int $id,
		AdventureRepository $adventureRepository,
		AdventurePointRepository $adventurePointRepository
	): JsonResponse {
		$adventure = $adventureRepository->find($id);
		if (!$adventure) {
			return new JsonResponse(['error' => 'Adventure not found.'], 404);
		}
		$points = $adventurePointRepository->findBy(['adventure' => $adventure], ['recordedAt' => 'ASC']);
		$result = array_map(fn($pt) => [
			'lat' => $pt->getLatitude(),
			'lng' => $pt->getLongitude(),
			'elev' => $pt->getElevation(),
			'time' => $pt->getRecordedAt()->format('c')
		], $points);

		return new JsonResponse(['points' => $result]);
	}

}