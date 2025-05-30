<?php


namespace App\Controller\Guest;

use App\Entity\User;
use App\Service\ContactListManager;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class GuestLoginController extends AbstractController {


	#[Route('/login', name: 'login', methods: ['GET', 'POST'])]
	public function displayLogin(Request $request, AuthenticationUtils $authenticationUtils): Response {
		$error = $authenticationUtils->getLastAuthenticationError();
	$lastUsername = $authenticationUtils->getLastUsername();

		return $this->render('guest/login.html.twig', [
			'last_username' => $lastUsername,
			'error' => $error
		]);
	}

	#[Route('/logout', name: "logout", methods: ['GET'])]
	public function logout() {

	}

    #[Route('/register', name: "register", methods: ['GET', 'POST'])]
	public function displayRegister(
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

            if ($email !== $confirmEmail || $password !== $confirmPass) {
            	$this->addFlash('error', 'Email ou mot de passe non confirmé.');
        	} else {
            	$user = new User();
            	// Le mot de passe est hashé par la fonction intégrée par Symfony
				$passwordHashed = $userPasswordHasher->hashPassword($user, $password);

				$user->createUser(
					$email,
					$passwordHashed,
					$name,
					$surname,
					new \DateTime($birthdate)
				);


				try {
					$entityManager->persist($user);
					$entityManager->flush();

					// 💡 Création des listes "Default" + 3 customs
                    $contactListManager->initializeDefaultAndCustomLists($user);
					$this->addFlash('success','Utilisateur créé'); 
					return $this->redirectToRoute('login');

				} catch(Exception $exception) {
					$this->addFlash('error', 'Erreur lors de la création du compte.');

					// Si le code erreur est 1062 (clé d'unicité), le message est complété par l'information relative à cette contrainte non respectée.
					if ($exception->getCode() === 1062) {
						$this->addFlash('error',  'Email déjà pris.');
					}
				}
        	}
		}

		return $this->render('/guest/register.html.twig');
	}
}

?>