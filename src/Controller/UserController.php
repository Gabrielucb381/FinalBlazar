<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\UserRepository;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    public function __construct(private readonly UserRepository $userRepository)
    {
    }

    #[Route('/users', name: 'users')]
    public function index(): Response
    {
        $users = $this->userRepository->findAllUsers();

        return $this->json($users);
    }

    public function show(string $email): Response
    {
        $user = $this->userRepository->findUserByEmail($email);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        return $this->json($user);
    }
}