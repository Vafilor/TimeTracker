<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Tag;
use App\Form\Model\TagEditModel;
use App\Form\Model\TagModel;
use App\Form\Model\UserEditModel;
use App\Form\TagEditFormType;
use App\Form\TagFormType;
use App\Form\UserEditFormType;
use App\Repository\TagRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends BaseController
{
    #[Route('/user/{id}/view', name: 'user_view')]
    public function list(Request $request): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $user = $this->getUser();
        $model = UserEditModel::fromEntity($user);
        $form = $this->createForm(UserEditFormType::class, $model);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UserEditModel $data */
            $data = $form->getData();

            $user->setTimezone($data->getTimezone());
            $this->getDoctrine()->getManager()->flush();
        }

        return $this->render('user/view.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
}