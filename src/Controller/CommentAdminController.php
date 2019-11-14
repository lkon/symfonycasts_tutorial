<?php

namespace App\Controller;

use App\Repository\CommentRepository;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class CommentAdminController
 * @package App\Controller
 * @IsGranted("ROLE_ADMIN")
 */
class CommentAdminController extends AbstractController
{
    /**
     * @Route("/comment/admin", name="comment_admin")
     * @IsGranted("ROLE_ADMIN")
     */
    public function index(CommentRepository $repository, Request $request, PaginatorInterface $paginator)
    {
//        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $q = $request->query->get('q');
        $queryBuilder = $repository->getWithSearchQueryBuilder($q);

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            2
        );

        return $this->render('comment_admin/index.html.twig', [
            'pagination'=> $pagination
        ]);
    }
}
