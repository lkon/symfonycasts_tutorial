<?php


namespace App\Controller;


use App\Entity\Article;
use App\Entity\ArticleReference;
use App\Service\UploaderHelper;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Mapping\Annotation\TreePathHash;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ArticleReferenceAdminController extends BaseController
{
    /**
     * @Route(
     *     "/admin/article/{id}/references",
     *     name="admin_article_add_reference",
     *     methods={"POST"}
     * )
     * @IsGranted("MANAGE", subject="article")
     */
    public function uploadArticleReference(
        Article $article,
        Request $request,
        UploaderHelper $uploaderHelper,
        EntityManagerInterface $entityManager,
        // This is the service that the form system
        // uses internally for validation.
        ValidatorInterface $validator
    )
    {
        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $request->files->get('reference');

        $violations = $validator->validate(
            $uploadedFile,
            [
                new NotBlank([
                    'message' => 'Please select a file to upload',
                ]),
                new File([
                    'maxSize' => '5M',
                    'mimeTypes' => [
                        'image/*',
                        'application/pdf',
                        'application/msword',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                        'text/plain',
                    ],
                ]),
            ]
        );

        if ($violations->count() > 0) {
//            /** @var ConstraintViolation $violation */
//            $violation = $violations[0];
//            $this->addFlash('error', $violation->getMessage());
//
//            return $this->redirectToRoute('admin_article_edit', [
//                'id' => $article->getId(),
//            ]);

            return $this->json($violations, 400);
        }


        $filename = $uploaderHelper->uploadArticleReference($uploadedFile);

        $articleReference = new ArticleReference($article);
        $articleReference
            ->setFilename($filename)
            ->setOriginalFilename($uploadedFile->getClientOriginalName() ?? $filename)
            // default it to application/octet-stream,
            // which is sort of a common way to say "I have no idea what this file is"
            ->setMimeType($uploadedFile->getMimeType() ?? 'application/octet-stream');

        $entityManager->persist($articleReference);
        $entityManager->flush();

//        return $this->redirectToRoute('admin_article_edit', [
//            'id' => $article->getId(),
//        ]);

        return $this->json(
            $articleReference,
            201, //that's the proper status code when you've created a resource.
            [],
            [
                'groups' => ['main'],
            ]
        );
    }

    /**
     * @Route(
     *     "/admin/article/references/{id}/download",
     *     name="admin_article_download_reference",
     *     methods={"GET"}
     * )
     */
    public function downloadArticleReference(
        ArticleReference $reference,
        UploaderHelper $uploaderHelper
    )
    {
        $article = $reference->getArticle();
        $this->denyAccessUnlessGranted("MANAGE", $article);

        $response = new StreamedResponse(function () use ($reference, $uploaderHelper) {
            /**
             * We usually use fopen to write to a file. But this special php://output
             * allows us to write to the "output" stream - a fancy way of saying
             * that anything we write to this stream will just get "echo'ed" out.
             */
            $outputStream = fopen('php://output', 'wb');
            $fileStream = $uploaderHelper->readStream($reference->getFilePath(), false);

            stream_copy_to_stream($fileStream, $outputStream);
        });

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $reference->getOriginalFilename()
        );

        $response->headers->set('Content-Type', $reference->getMimeType());
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    /**
     * @Route(
     *     "/admin/article/{id}/references",
     *     name="admin_article_list_references",
     *     methods={"GET"}
     * )
     * @IsGranted("MANAGE", subject="article")
     */
    public function getArticleReferences(Article $article)
    {
        return $this->json(
            $article->getArticleReferences(),
            200,
            [],
            [
                'groups' => ['main'],
            ]
        );
    }

    /**
     * @Route(
     *     "/admin/article/references/{id}",
     *     name="admin_article_delete_reference",
     *     methods={"DELETE"}
     * )
     */
    public function deleteArticleReference(ArticleReference $reference, UploaderHelper $uploaderHelper)
    {
        $article = $reference->getArticle();
        $this->denyAccessUnlessGranted('MANAGE', $article);

        $uploaderHelper->deleteFile($reference->getFilePath(), false);

        //the operation was successful but I have nothing else to say!
        return new Response(null, 204);
    }

    /**
     * @Route(
     *     "/admin/article/references/{id}",
     *     name="admin_article_update_reference",
     *     methods={"PUT"}
     * )
     */
    public function updateArticleReference(
        ArticleReference $reference,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        Request $request,
        ValidatorInterface $validator
    )
    {
        $article = $reference->getArticle();
        $this->denyAccessUnlessGranted('MANAGE', $article);

        $serializer->deserialize(
            $request->getContent(),
            ArticleReference::class,
            'json',
            [
                'object_to_populate' => $reference,
                'groups' => ['input'],
            ]
        );

        $violations = $validator->validate($reference);
        if ($violations->count() > 0) {
            return $this->json($violations, 400);
        }

        $entityManager->persist($reference);
        $entityManager->flush();

        return $this->json(
            $reference,
            200,
            [],
            [
                'groups' => ['main'],
            ]
        );
    }

    /**
     * @Route(
     *     "/admin/article/{id}/references/reorder",
     *     name="admin_article_reorder_references",
     *     methods={"POST"}
     * )
     */
    public function reorderArticleReferences(
        Article $article,
        Request $request,
        EntityManagerInterface $entityManager
    )
    {
        $orderedIds = json_decode($request->getContent(), true);

//        dump($orderedIds);

        if ($orderedIds === null) {
            return $this->json(['detail' => 'Invalid body'], 400);
        }
        // from (position)=>(id) to (id)=>(position)
        $orderedIds = array_flip($orderedIds);
//        dump($orderedIds);
        foreach ($article->getArticleReferences() as $reference) {
            $reference->setPosition($orderedIds[$reference->getId()]);
//            dump($reference->getPosition());
        }

        $entityManager->flush();

//        dd($article->getArticleReferences());

        return $this->json(
            $article->getArticleReferences(),
            200,
            [],
            [
                'groups' => ['main'],
            ]
        );
    }
}
