<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User as UserEntity;

class ProfileController extends AbstractController
{
    #[Route('/profil', name: 'app_profile')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        // The user is guaranteed to be logged in at this point
        $user = $this->getUser();

        return $this->render('profile/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/profil/parametres', name: 'app_profile_settings', methods: ['GET','POST'])]
    #[IsGranted('ROLE_USER')]
    public function settings(Request $request, ManagerRegistry $doctrine): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $token = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('profile_settings', $token)) {
                $this->addFlash('danger', 'Jeton CSRF invalide.');
                return $this->redirectToRoute('app_profile_settings');
            }

            $prenom = $request->request->get('prenom');
            $nom = $request->request->get('nom');
            $email = $request->request->get('email');
            $licence = $request->request->get('licence');

            // Handle avatar upload
            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $avatarFile */
            $avatarFile = $request->files->get('avatar');
            if ($avatarFile) {
                $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars';
                if (!is_dir($uploadsDir)) {
                    mkdir($uploadsDir, 0755, true);
                }
                $originalName = pathinfo($avatarFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeName = preg_replace('/[^a-zA-Z0-9-_\.]/', '-', $originalName);

                // remember old avatar to delete it after successful upload
                $oldAvatar = $user->getAvatar();

                // Try to determine extension safely. guessExtension() may rely on the fileinfo extension.
                try {
                    $extension = $avatarFile->guessExtension();
                } catch (\Throwable $e) {
                    $extension = null;
                }
                if (!$extension) {
                    // fallback to client provided extension
                    $extension = $avatarFile->getClientOriginalExtension();
                }
                $extension = strtolower(trim((string) $extension));
                // sanitize extension and fallback
                if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif'], true)) {
                    // unknown or potentially unsafe extension -> default to png
                    $extension = 'png';
                }

                $newFilename = $safeName.'-'.uniqid().'.'.$extension;
                try {
                    $avatarFile->move($uploadsDir, $newFilename);
                    $user->setAvatar($newFilename);

                    // delete previous avatar file if exists and is different
                    if ($oldAvatar) {
                        $oldPath = $uploadsDir . DIRECTORY_SEPARATOR . $oldAvatar;
                        if (is_file($oldPath) && $oldAvatar !== $newFilename) {
                            @unlink($oldPath);
                        }
                    }
                } catch (\Exception $e) {
                    $this->addFlash('danger', 'Impossible d\'uploader l\'avatar.');
                }
            }

            if ($prenom !== null) {
                $user->setPrenom(trim($prenom));
            }
            if ($nom !== null) {
                $user->setNom(trim($nom));
            }
            if ($email !== null) {
                $user->setEmail(trim($email));
            }
            if ($licence !== null) {
                $user->setLicence(trim($licence));
            }

            $em = $doctrine->getManager();
            $em->persist($user);
            $em->flush();

            // Refresh user from DB to ensure latest avatar is used in the security token and templates
            $freshUser = $doctrine->getRepository(UserEntity::class)->find($user->getId());
            if ($freshUser) {
                // Update the token user so app.user reflects changes immediately
                try {
                    $tokenStorage = $this->container->get('security.token_storage');
                    $token = $tokenStorage ? $tokenStorage->getToken() : null;
                    if ($token) {
                        $token->setUser($freshUser);
                    }
                } catch (\Throwable $e) {
                    // silent failure; not critical
                }
            }

            $this->addFlash('success', 'Paramètres enregistrés.');

            return $this->redirectToRoute('app_profile_settings');
        }

        return $this->render('profile/settings.html.twig', [
            'user' => $user,
        ]);
    }
}
