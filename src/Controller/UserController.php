<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\Model\UserEditModel;
use App\Form\UserEditFormType;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends BaseController
{
    #[Route('/user/{id}/view', name: 'user_view')]
    public function view(Request $request, UserRepository $userRepository, string $id): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $user = $userRepository->find($id);
        if (is_null($user)) {
            throw $this->createNotFoundException();
        }

        if (!$this->getUser()->equalIds($user)) {
            throw $this->createAccessDeniedException();
        }

        $model = UserEditModel::fromEntity($user);
        $form = $this->createForm(UserEditFormType::class, $model);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UserEditModel $data */
            $data = $form->getData();

            $user->setTimezone($data->getTimezone());
            $user->setDurationFormat($data->getDurationFormat());
            $user->setDateTimeFormat($data->getDateTimeFormat());
            $user->setTodayDateTimeFormat($data->getTodayDateTimeFormat());

            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', 'User settings updated');
        }

        return $this->render('user/view.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
}