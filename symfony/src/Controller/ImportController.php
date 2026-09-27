<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/import')]
#[IsGranted('ROLE_ADMIN')]
final class ImportController extends AbstractController
{
    #[Route('/', name: 'app_import', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {

        if ($request->isMethod('POST')) {

            $file = $request->files->get('csv');

            if ($file) {

                try {

                    $handle = fopen($file->getPathname(), 'r');

                    fgetcsv($handle);

                    while (($data = fgetcsv($handle, 1000, ",")) !== false) {

                        if (count($data) < 3) {
                            continue;
                        }

                        $username = trim($data[0]);
                        $email = trim($data[1]);
                        $password = trim($data[2]);

                        if ($entityManager->getRepository(User::class)->findOneBy([
                            'username' => $username
                        ])) {
                            continue;
                        }

                        $user = new User();

                        $user->setUsername($username);
                        $user->setEmail($email);
                        $user->setRoles(['ROLE_USER']);

                        $user->setPassword(
                            $passwordHasher->hashPassword($user, $password)
                        );

                        $entityManager->persist($user);
                    }

                    fclose($handle);

                    $entityManager->flush();

                    $this->addFlash('success', 'Import terminé avec succès.');

                } catch (FileException $e) {

                    $this->addFlash('danger', 'Erreur lors de la lecture du fichier.');

                }

            }

        }

        return $this->render('import/index.html.twig');
    }
}