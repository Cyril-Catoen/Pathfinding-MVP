<?php 

namespace App\Controller\Admin;

use App\Entity\Adventure;
use App\Repository\AdventureRepository;
use App\Entity\AdventureFile;
use App\Repository\AdventureFileRepository;
use App\Entity\AdventurePicture;
use App\Repository\AdventurePictureRepository;
use App\Repository\AdventurePointRepository;
use App\Entity\AdventureType;
use App\Repository\AdventureTypeRepository;
use App\Service\AdventureTypeIconHelper;
use App\Entity\ContactList;
use App\Repository\ContactListRepository;
use App\Entity\SafetyAlert;
use App\Repository\SafetyAlertRepository;
use App\Entity\SafetyContact;
use App\Repository\SafetyContactRepository;
use App\Entity\TimerAlert;
use App\Repository\TimerAlertRepository;
use App\Enum\Status;
use App\Enum\ViewAuthorization;
use App\Enum\AdventureTypeList;
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

class AdminAdventureController extends AbstractController {
	
    #[Route('/admin/adventure-manager', name: 'admin/adventure-manager', methods: ['GET'])]
	public function displayListUser(AdventureRepository $AdventureRepository): Response {
        // On utilise les composants de Symfony pour gérer le hash du Password et la création de l'utilisateur

            // Récupérer tous les users
			$adventures = $AdventureRepository->findAll();
			
			$sorts = [];

            foreach ($adventures as $adventure) {
				$sorts[]= $adventure;
		    }

		// Tri des autres aventures (date début DESC)
		usort($sorts, fn($a, $b) => $b->getId() <=> $a->getId());
		
		return $this->render('/admin/adventures/adventure-manager.html.twig', [ 
            'adventures' => $adventures,
            'sorts' => $sorts,
        ]);

	}

    #[Route('/admin/adventure/{id}', name: 'display-adventure', methods: ['GET', 'POST'])]
	public function displayAdventure(
		Adventure $adventure,
		AdventureRepository $adventureRepository,
		AdventurePictureRepository $pictureRepository,
        AdventurePointRepository $adventurePointRepository,
		ContactListRepository $contactListRepository,
		Security $security
	): Response {
		$user = $security->getUser();

		// 🖼️ Récupération des images liées à l'aventure
		$pictures = $pictureRepository->findBy(
			['adventure' => $adventure],
			['position' => 'ASC', 'uploadedAt' => 'ASC']);

		// Supposons que $adventure->getOwner() existe et que getContactLists() renvoie ses listes.
        $owner = $adventure->getOwner();
        $contactLists = $owner ? $contactListRepository->findBy(['owner' => $owner]) : [];
        $selectedContactList = $adventure->getContactList(); // ou le champ équivalent

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

        return $this->render('admin/adventures/display-adventure.html.twig', [
            'adventure' => $adventure,
            'contactLists' => $contactLists,
            'selectedContactList' => $selectedContactList,
            'pictures' => $pictures,
            'points' => $points,
			'timerDuration' => $timerDuration,
            'types' => $adventure->getTypes(), // liste des types sélectionnés par l'user à la création
			'adventureTypes' => AdventureTypeList::cases(), // afficher la liste complète pour édition
			'typeIcons' => AdventureTypeIconHelper::getIconMap(),
        ]);
    }}

    #[Route('/admin/delete-adventure/{id}', name: 'admin/delete-adventure', methods: ['GET', 'POST'])]
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
            return $this->redirectToRoute('admin/adventure-manager');
        }

        // Vérifie que l'utilisateur est admin
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->addFlash('error', 'You cannot delete an adventure if you are not admin.');
            return $this->redirectToRoute('admin/adventure-manager');
        }

        $entityManager->remove($adventure);
        $entityManager->flush();

        $this->addFlash('success', 'Adventure successfully removed.');

        return $this->redirectToRoute('admin/adventure-manager');
    }


}

?>