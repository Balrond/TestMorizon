<?php

namespace App\Controller;

use App\Dto\UserFilters;
use App\Dto\UserInput;
use App\Form\UserFiltersType;
use App\Form\UserType;
use App\Http\PhoenixUsersClientInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/users')]
final class UserController extends AbstractController
{
    public function __construct(
        private readonly PhoenixUsersClientInterface $client
    ) {}

    #[Route('', name: 'users_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $filters = new UserFilters();
        $form = $this->createForm(UserFiltersType::class, $filters);
        $form->handleRequest($request);

        $query = array_filter([
            'first_name' => $filters->first_name,
            'last_name' => $filters->last_name,
            'gender' => $filters->gender,
            'birthdate_from' => $filters->birthdate_from,
            'birthdate_to' => $filters->birthdate_to,
            'sort' => $request->query->get('sort'),
            'dir' => $request->query->get('dir'),
        ], fn($v) => $v !== null && $v !== '');

        $data = $this->client->list($query);
        $users = $data['data'] ?? $data;

        return $this->render('users/index.html.twig', [
            'form' => $form,
            'users' => $users,
            'sort' => $query['sort'] ?? null,
            'dir' => $query['dir'] ?? null,
        ]);
    }

    #[Route('/new', name: 'users_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $dto = new UserInput();
        $form = $this->createForm(UserType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $res = $this->client->create(['user' => (array) $dto]);

            if (($res['_status'] ?? 200) >= 400) {
                $this->addFlash('error', 'Nie udało się utworzyć użytkownika.');
                return $this->render('users/new.html.twig', [
                    'form' => $form,
                    'api_error' => $res['errors'] ?? ($res['_error'] ?? null),
                ]);
            }

            $this->addFlash('success', 'Użytkownik utworzony.');
            return $this->redirectToRoute('users_index');
        }

        return $this->render('users/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id<\d+>}', name: 'users_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $res = $this->client->get($id);

        if (($res['_status'] ?? 200) === 404) {
            throw $this->createNotFoundException();
        }

        return $this->render('users/show.html.twig', [
            'user' => $res['data'] ?? $res,
        ]);
    }

    #[Route('/{id<\d+>}/edit', name: 'users_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request): Response
    {
        $existing = $this->client->get($id);
        if (($existing['_status'] ?? 200) === 404) {
            throw $this->createNotFoundException();
        }
        $u = $existing['data'] ?? $existing;

        $dto = new UserInput();
        $dto->first_name = $u['first_name'] ?? null;
        $dto->last_name = $u['last_name'] ?? null;
        $dto->gender = $u['gender'] ?? null;
        $dto->birthdate = $u['birthdate'] ?? null;

        $form = $this->createForm(UserType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $res = $this->client->update($id, ['user' => (array) $dto]);

            if (($res['_status'] ?? 200) >= 400) {
                $this->addFlash('error', 'Nie udało się zapisać zmian.');
                return $this->render('users/edit.html.twig', [
                    'form' => $form,
                    'user' => $u,
                    'api_error' => $res['errors'] ?? ($res['_error'] ?? null),
                ]);
            }

            $this->addFlash('success', 'Zapisano zmiany.');
            return $this->redirectToRoute('users_show', ['id' => $id]);
        }

        return $this->render('users/edit.html.twig', [
            'form' => $form,
            'user' => $u,
        ]);
    }

    #[Route('/{id<\d+>}', name: 'users_delete', methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('delete_user_'.$id, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $this->client->delete($id);
        $this->addFlash('success', 'Użytkownik usunięty.');

        return $this->redirectToRoute('users_index');
    }
}
