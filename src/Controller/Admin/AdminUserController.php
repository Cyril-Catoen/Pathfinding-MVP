<?php


namespace App\Controller\Admin;

use App\Entity\User;
use App\Service\ContactListManager;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Intl\Countries; // permet de récuéprer les codes pays ISO et leurs noms pour le formulaire 
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class AdminUserController extends AbstractController {

	#[Route('/admin/create-user', name: 'create-user', methods: ['GET', 'POST'])]
	public function displayCreateUser(
		Request $request, 
		UserPasswordHasherInterface $userPasswordHasher, 
		EntityManagerInterface $entityManager,
		ContactListManager $contactListManager
		): Response {

        // On utilise les composants de Symfony pour gérer le hash du Password et la création de l'utilisateur

		if ($request->isMethod('POST')) {

            // Si le formulaire est soumis, on récupère dans deux variables distinctes l'email (identifiant futur de l'user) et le hash du mot de passe généré
			$email = $request->request->get('email');
			$confirmEmail = $request->request->get('confirmEmail');
			$password = $request->request->get('password');
			$confirmPass = $request->request->get('confirmPass');
			$name = $request->request->get('name');
			$surname = $request->request->get('surname');
			$birthdate = $request->request->get('birthdate');
			$role = $request->request->get('role');

            if ($role === null || !in_array($role, ['user', 'admin'])) {
				$this->addFlash('error', 'You must select a role (User or Admin).');
			} elseif ($email !== $confirmEmail || $password !== $confirmPass) {
				$this->addFlash('error', 'Email ou mot de passe non confirmé.');
			} else {
				$user = new User();
				$passwordHashed = $userPasswordHasher->hashPassword($user, $password);

				if ($role === 'admin') {
					$user->createAdmin(
						$email,
						$passwordHashed,
						$name,
						$surname,
						new \DateTime($birthdate)
					);
				} else {
					$user->createUser(
						$email,
						$passwordHashed,
						$name,
						$surname,
						new \DateTime($birthdate)
					);
				}

				try {
					$entityManager->persist($user);
					$entityManager->flush();

					// 💡 Création des listes "Default" + 3 customs
                    $contactListManager->initializeDefaultAndCustomLists($user);
					$this->addFlash('success','Utilisateur créé'); 
					return $this->redirectToRoute('admin/user-manager');

				} catch(Exception $exception) {
					$this->addFlash('error', 'Something went wrong. User not created.');

					// Si le code erreur est 1062 (clé d'unicité), le message est complété par l'information relative à cette contrainte non respectée.
					if ($exception->getCode() === 1062) {
						$this->addFlash('error',  'Already taken email.');
					}
				}
        	}
		}

		return $this->render('/admin/users/create-user.html.twig');
	}


    #[Route('/admin/update-user/{id}', name: 'update-user', methods: ['GET', 'POST'])]
	public function updateUser(
		int $id,
		Request $request,
		EntityManagerInterface $entityManager,
		UserRepository $userRepository
	): Response {
		// 1. Récupération de l'utilisateur à éditer
		$user = $userRepository->find($id);
		if (!$user) {
			$this->addFlash('error', 'User not found');
			return $this->redirectToRoute('admin/user-manager');
		}

		// 2. Pour la liste des pays
		$countries = Countries::getNames('en');

		// 3. Traitement du formulaire si POST
		if ($request->isMethod('POST')) {
			// On récupère les champs
			$name = $request->request->get('name');
			$surname = $request->request->get('surname');
			$email = $request->request->get('email');
			$birthdate = $request->request->get('birthdate');
			$country = $request->request->get('country');
			$role = $request->request->get('role');

			// Modification des valeurs
			$user->setName($name);
			$user->setSurname($surname);
			$user->setEmail($email);
			$user->setBirthdate(new \DateTime($birthdate));
			$user->setCountry($country);

			// Gestion du rôle
			if ($role === 'admin') {
				$user->setRoles(['ROLE_ADMIN']);
			} else {
				$user->setRoles(['ROLE_USER']);
			}

			// Gestion image (optionnelle)
			if ($request->files->get('picture-user')) {
				$file = $request->files->get('picture-user');
				$filename = uniqid().'.'.$file->guessExtension();
				$file->move($this->getParameter('user_pictures_directory'), $filename);
				$user->setPicturePath($filename);
			}

			$entityManager->flush();
			$this->addFlash('success', 'User updated!');
			return $this->redirectToRoute('admin/user-manager');
		}

		// 4. Affichage du formulaire pré-rempli
		return $this->render('/admin/users/update-user.html.twig', [
			'user' => $user,
			'countries' => $countries,
		]);
	}


    #[Route('/admin/delete-user/{id}', name: 'delete-user', methods: ['GET', 'POST'])]
	public function deleteUser(
		int $id,
		UserRepository $UserRepository,
		EntityManagerInterface $entityManager
		): Response {
        // On cible le user à supprimer par son id unique.
			$user = $UserRepository->find($id);
		
			 if (!$user) {
				$this->addFlash('error', 'User not found.');
                return $this->redirectToRoute('/admin/user-manager');
            }

            try {
                // On utilise la méthode remove de la classe EntityManager 
                // On prend en paramètre le user à supprimer
                $entityManager->remove($user);
                $entityManager->flush();
	
	    		// On ajoute un message flash pour notifier que le produit est supprimé
		    	$this->addFlash('success', 'The user has been deleted');
                return $this->redirectToRoute('admin/user-manager');

            } catch(Exception $exception) {
                $this->addFlash('error', "Something went wrong.");
            }
            
			// On redirige vers la page de liste mis à jour
			return $this->redirectToRoute('admin/user-manager');
		}

    #[Route('/admin/user-manager', name: 'admin/user-manager', methods: ['GET'])]
	public function displayListUser(UserRepository $UserRepository): Response {
        // On utilise les composants de Symfony pour gérer le hash du Password et la création de l'utilisateur

            // Récupérer tous les users
			$users = $UserRepository->findAll();
			
			$sorts = [];

            foreach ($users as $user) {
				$sorts[]= $user;
		    }

		// Tri des autres aventures (date début DESC)
		usort($sorts, fn($a, $b) => $b->getId() <=> $a->getId());
		
		return $this->render('/admin/users/user-manager.html.twig', [ 
            'users' => $users,
            'sorts' => $sorts,
        ]);

	}
    }
