<?php 

namespace App\Controller\User;

use App\Entity\Adventure;
use App\Repository\AdventureRepository;
use App\Entity\AdventureFile;
use App\Repository\AdventureFileRepository;
use App\Entity\AdventureType;
use App\Repository\AdventureTypeRepository;
use App\Entity\SafetyAlert;
use App\Repository\SafetyAlertRepository;
use App\Entity\SafetyContact;
use App\Repository\SafetyContactRepository;
use App\Entity\ContactList;
use App\Repository\ContactListRepository;
use App\Entity\TimerAlert;
use App\Repository\TimerAlertRepository; 
use App\Service\ContactListManager;
use Symfony\Component\Intl\Countries; // permet de récuéprer les codes pays ISO et leurs noms pour le formulaire de création de contacts
use Exception;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserContactsController extends AbstractController {

    #[Route('/user/contacts-manager', name: 'contacts-manager', methods: ['GET', 'POST'])]
    public function displayListContacts(
		Request $request, 
		SafetyContactRepository $safetyContactRepository,
		ContactListRepository $contactListRepository, 
		EntityManagerInterface $entityManager, 
		ParameterBagInterface $parameterBag
	): Response {

		$user = $this->getUser();
		$safetyContacts = $safetyContactRepository->findBy(['user' => $user]);
		$contactLists = $contactListRepository->findBy(['owner' => $user]);
		
		$selectedListId = $request->query->get('list'); // Permet le changement dynamique par JS ou lien
		$selectedList = $selectedListId ? $contactListRepository->find($selectedListId) : ($contactLists[0] ?? null);
		$selectedListId = $selectedList?->getId();

		// Organisation des contacts
		$members = [];
		$nonMembers = [];
		$contactMembershipMap = [];

		foreach ($safetyContacts as $contact) {
			$isInList = $contact->getContactLists()->exists(fn($key, $list) => $list->getId() === $selectedListId);
			$contactMembershipMap[$contact->getId()] = $isInList;

			if ($isInList) {
				$members[] = $contact;
			} else {
				$nonMembers[] = $contact;
			}
		}

		// 1. Tri des membres (toujours alphabétique)
		usort($members, fn($a, $b) => strcasecmp(
			$a->getFirstName() . $a->getLastName(),
			$b->getFirstName() . $b->getLastName()
		));

		// 2. Choix du tri pour nonMembers selon le paramètre sort
		$sort = $request->query->get('sort', 'az');

		if ($sort === 'az') {
			// alphabétique A → Z
			usort($nonMembers, fn($a, $b) => strcasecmp(
				$a->getFirstName() . $a->getLastName(),
				$b->getFirstName() . $b->getLastName()
			));
		} elseif ($sort === 'za') {
			// alphabétique Z → A (si tu gères aussi ce cas)
			usort($nonMembers, fn($a, $b) => -strcasecmp(
				$a->getFirstName() . $a->getLastName(),
				$b->getFirstName() . $b->getLastName()
			));
		} elseif ($sort === 'old') {
			// tri par date ancien → récent (si tu gères aussi)
			usort($nonMembers, fn($a, $b) => $a->getCreatedAt() <=> $b->getCreatedAt());
		} else {
			// par défaut 'recent' ou tout autre, récent → ancien
			usort($nonMembers, fn($a, $b) => $b->getCreatedAt() <=> $a->getCreatedAt());
		}


		return $this->render('user/Contacts/contacts-manager.html.twig', [
			'safetyContacts' => $safetyContacts, // Pour onglet "All contacts"
			'contactLists' => $contactLists,
			'selectedListId' => $selectedListId,
			'members' => $members, // safetyContacts est décomposé en 2 groupe "members" et "non members" de la SafetyList
			'nonMembers' => $nonMembers,
			'contactMembershipMap' => $contactMembershipMap,
    	]);
    }

	#[Route('/user/contacts-ajax', name: 'contacts-ajax', methods: ['GET'])]
	public function ajaxContactList(
		Request $request,
		SafetyContactRepository $safetyContactRepository,
		ContactListRepository $contactListRepository
	): Response {
		$user = $this->getUser();
		$selectedListId = $request->query->get('list');
		$sort = $request->query->get('sort', 'recent');

		$safetyContacts = $safetyContactRepository->findBy(['user' => $user]);

		$members = [];
		$nonMembers = [];

		foreach ($safetyContacts as $contact) {
			$isInList = $contact->getContactLists()->exists(fn($k, $l) => $l->getId() === (int) $selectedListId);
			if ($isInList) {
				$members[] = $contact;
			} else {
				$nonMembers[] = $contact;
			}
		}

		usort($members, fn($a, $b) => strcasecmp($a->getFirstName() . $a->getLastName(), $b->getFirstName() . $b->getLastName()));

		$sort = $request->query->get('sort', 'az'); // Par défaut : az

		if (in_array($sort, ['az', 'za'])) {
			usort($nonMembers, function($a, $b) use ($sort) {
				$cmp = strcasecmp($a->getFirstName() . $a->getLastName(), $b->getFirstName() . $b->getLastName());
				return $sort === 'az' ? $cmp : -$cmp;
			});
		} elseif (in_array($sort, ['recent', 'old'])) {
			usort($nonMembers, function($a, $b) use ($sort) {
				$cmp = $b->getCreatedAt() <=> $a->getCreatedAt();
				return $sort === 'recent' ? $cmp : -$cmp;
			});
		}

		return $this->render('user/partials/_contact-table.html.twig', [
			'members' => $members,
			'nonMembers' => $nonMembers,
			'selectedListId' => $selectedListId,
		]);
	}

	#[Route('/user/update-contact-list', name: 'update-contact-list', methods: ['POST'])]
	public function updateContactList(
		Request $request,
		ContactListRepository $contactListRepository,
		SafetyContactRepository $contactRepository,
		EntityManagerInterface $em,
		Security $security
	): Response {
		$user = $security->getUser();
		$listId = $request->request->get('list');
		$contactIds = $request->request->all('contacts'); // tableau d'IDs cochés

		// Récupération de la liste appartenant à l'utilisateur
		/** @var ContactList $list */
		$list = $contactListRepository->findOneBy(['id' => $listId, 'owner' => $user]);
		if (!$list) {
			$this->addFlash('error', 'Liste introuvable ou accès non autorisé.');
			return $this->redirectToRoute('contacts-manager');
		}

		// Récupération des contacts cochés appartenant aussi à l'utilisateur
		$selectedContacts = [];
		if (!empty($contactIds)) {
			$selectedContacts = $contactRepository->createQueryBuilder('c')
				->where('c.id IN (:ids)')
				->andWhere('c.user = :user')
				->setParameter('ids', $contactIds)
				->setParameter('user', $user)
				->getQuery()
				->getResult();
		}

		// ⚠️ Validation des règles métier
		if ($list->isDefault() && count($selectedContacts) < 2) {
			$this->addFlash('error', 'La liste par défaut doit contenir au moins 2 contacts.');
			return $this->redirectToRoute('contacts-manager', ['_fragment' => 'tab-safety']);
		}

		if (count($selectedContacts) > 5) {
			$this->addFlash('error', 'Une liste ne peut pas contenir plus de 5 contacts.');
			return $this->redirectToRoute('contacts-manager', ['_fragment' => 'tab-safety']);
		}

		// 🔄 Mise à jour de la liste
		$list->clearContacts();
		foreach ($selectedContacts as $contact) {
			$list->addContact($contact);
		}

		$em->flush();

		// ✅ Message adapté
		if (!$list->isDefault() && count($selectedContacts) < 2) {
			$this->addFlash('warning', 'Il est recommandé d’avoir au moins 2 contacts dans une liste.');
		} else {
			$this->addFlash('success', 'Liste mise à jour avec succès.');
		}

		return $this->redirectToRoute('contacts-manager', ['_fragment' => 'tab-safety']);
	}



    #[Route('/user/update-contact/{id}', name: 'update-contact', methods: ['GET', 'POST'])]
	public function displayUpdateSafetyContact(
		int $id,
		Request $request,
		SafetycontactRepository $safetycontactRepository,
		EntityManagerInterface $entityManager,
		ContactListManager $contactListManager,
		ContactListRepository $contactlistRepository,
		ParameterBagInterface $parameterBag
	): Response {
		$user = $this->getUser();
		$safetyContact = $safetycontactRepository->find($id);

		if (!$safetyContact || $safetyContact->getUser() !== $user) {
			throw $this->createNotFoundException('Contact not found or access denied.');
		}

		$countries = Countries::getNames('en');
		$contactLists = $contactlistRepository->findBy(['owner' => $user]);

		if ($request->isMethod('POST')) {
			$data = $request->request;
			$picture_contact = $request->files->get('picture-contact');

			$selectedListIds = $data->all('contactLists');
			$selectedLists = [];

			foreach ($selectedListIds as $listId) {
				$list = $contactlistRepository->find($listId);
				if ($list && $list->getOwner() === $user) {
					$selectedLists[] = $list;
				}
			}

			$pictureNewName = $safetyContact->getPicturePath();
			if ($picture_contact) {
				$pictureNewName = uniqid() . '.' . $picture_contact->guessExtension();
				$targetDirectory = $parameterBag->get('contact_pictures_directory');
				$picture_contact->move($targetDirectory, $pictureNewName);
			}

			$safetyContact->updateSafetyContact(
				$data->get('email'),
				$data->get('name'),
				$data->get('surname'),
				$data->get('phone'),
				strtoupper($data->get('country')),
				$pictureNewName,
				$data->getBoolean('isFavorite'), // getBoolean() de Symfony transforme "1" ou "0" (string) en true/false.
				$selectedLists
			);

			try {
				$entityManager->flush();
				$this->addFlash('success', 'Contact successfully updated!');
				return $this->redirectToRoute('contacts-manager');
			} catch (\Exception $exception) {
				$this->addFlash('error', 'Something went wrong.');
			}
		}

		return $this->render('user/Contacts/update-contact.html.twig', [
			'safetyContact' => $safetyContact,
			'countries' => $countries,
			'contactLists' => $contactLists,
		]);
	}

   #[Route('/user/create-contact', name: 'create-contact', methods: ['GET', 'POST'])]
	public function displayCreateSafetyContact(
		Request $request, 
		EntityManagerInterface $entityManager, 
		ContactListManager $contactListManager,
		ContactListRepository $contactlistRepository, 
		ParameterBagInterface $parameterBag,
		ValidatorInterface $validator
	): Response {

		$user = $this->getUser();
		$contactListManager->initializeDefaultAndCustomLists($user);
		$countries = Countries::getNames('en');
		$contactLists = $contactlistRepository->findBy(['owner' => $user]);

		if ($request->isMethod('POST')) {
			$data = $request->request;
			$picture_contact = $request->files->get('picture-contact');

			$email = $data->get('email');
			$name = $data->get('name');
			$surname = $data->get('surname');
			$phone = $data->get('phone');
			$country = strtoupper($data->get('country'));
			$declarationOfMajority = $data->get('declarationOfMajority') === 'on';
			$listId = $data->get('contactList'); // récupère la seule liste sélectionnée
			$selectedLists = [];

			if ($listId) {
				$list = $contactlistRepository->find($listId);
				if ($list) {
					if ($list->getContacts()->count() >= 5) {
						$this->addFlash('error', 'La liste sélectionnée contient déjà 5 contacts.');
						return $this->redirectToRoute('create-contact');
					}
					$selectedLists[] = $list;
				}
			}

			// Upload image
			$pictureNewName = null;
			if ($picture_contact) {
				$pictureNewName = uniqid() . '.' . $picture_contact->guessExtension();
				$targetDirectory = $parameterBag->get('contact_pictures_directory');
				$picture_contact->move($targetDirectory, $pictureNewName);
			}

			$safetyContact = new SafetyContact();
			$safetyContact->createSafetyContact(
				$email,
				$name,
				$surname,
				$phone,
				$country,
				$pictureNewName,
				$declarationOfMajority,
				$user,
				$selectedLists // array d’objets ContactList
			);

			// 🔍 Validation Symfony / Back-end
			$errors = $validator->validate($safetyContact);

			if (count($errors) > 0) {
				foreach ($errors as $error) {
					$this->addFlash('error', sprintf('%s: %s', $error->getPropertyPath(), $error->getMessage()));
				}

				return $this->redirectToRoute('create-contact');
			}

			try {
				$entityManager->persist($safetyContact);
				$entityManager->flush();

				$this->addFlash('success', 'Contact successfully created!');
				return $this->redirectToRoute('contacts-manager');

			} catch(Exception $exception) {
				$this->addFlash('error', 'Something went wrong.');
			}
		}

		return $this->render('user/Contacts/create-contact.html.twig', [
			'countries' => $countries,
			'contactLists' => $contactLists
		]);
	}



	#[Route('/user/delete-contact/{id}', name: 'delete-contact', methods: ['GET', 'POST'])]
	public function displayDeleteSafetyContact(
		int $id,
		EntityManagerInterface $entityManager
	): Response {

		$contact = $entityManager->getRepository(SafetyContact::class)->find($id);

		if (!$contact) {
			$this->addFlash('error', 'Contact not found.');
			return $this->redirectToRoute('contacts-manager');
		}

		if ($contact->getUser() !== $this->getUser()) {
		$this->addFlash('error', 'Access denied.');
		return $this->redirectToRoute('contacts-manager');
		}

		try {
			$entityManager->remove($contact);
			$entityManager->flush();

			$this->addFlash('success', 'Contact successfully removed!');
			return $this->redirectToRoute('contacts-manager');
		} catch(Exception $exception) {
				$this->addFlash('error', 'Something went wrong.');
		}
	}
}