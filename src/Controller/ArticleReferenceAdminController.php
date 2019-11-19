<?php


namespace App\Controller;


use App\Entity\Article;
use App\Entity\ArticleReference;
use App\Service\UploaderHelper;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
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

        dump($uploadedFile);

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
            /** @var ConstraintViolation $violation */
            $violation = $violations[0];
            $this->addFlash('error', $violation->getMessage());

            return $this->redirectToRoute('admin_article_edit', [
                'id' => $article->getId(),
            ]);
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

        return $this->redirectToRoute('admin_article_edit', [
            'id' => $article->getId(),
        ]);
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
}