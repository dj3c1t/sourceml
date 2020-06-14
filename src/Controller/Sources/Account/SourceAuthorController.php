<?php

namespace Sourceml\Controller\Sources\Account;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Sourceml\Entity\Sources\Source;
use Sourceml\Entity\Sources\Author;
use Sourceml\Entity\Sources\AuthorRole;
use Sourceml\Entity\Sources\SourceAuthor;

class SourceAuthorController extends Controller {

    public function addAction(Request $request) {
        $data = $request->request->all();
        if(
                !isset($data["source"])
            ||  !isset($data["author"])
            ||  !isset($data["author_role"])
        ) {
            throw new \Exception("missing parameter");
        }
        $em = $this->get('doctrine')->getManager();
        $sourceRepo = $em->getRepository(Source::class);
        $authorRepo = $em->getRepository(Author::class);
        $authorRoleRepo = $em->getRepository(AuthorRole::class);
        $sourceAuthorRepo = $em->getRepository(SourceAuthor::class);
        if(!($source = $sourceRepo->find($data["source"]))) {
            throw new \Exception("can't find source id ".$data["source"]);
        }
        if(!($author = $authorRepo->find($data["author"]))) {
            throw new \Exception("can't find author id ".$data["author"]);
        }
        if(!($authorRole = $authorRoleRepo->find($data["author_role"]))) {
            throw new \Exception("can't find authorRole id ".$data["author_role"]);
        }
        if(
            $sourceAuthorRepo->findBy(
                array(
                    "author" => $author->getId(),
                    "source" => $source->getId(),
                )
            )
        ) {
            throw new \Exception("this author already have a role on this source");
        }
        $sourceAuthor = new SourceAuthor();
        $sourceAuthor->setSource($source);
        $sourceAuthor->setAuthor($author);
        $sourceAuthor->setAuthorRole($authorRole);
        $sourceAuthor->setIsValid(false);
        $em->persist($sourceAuthor);
        try {
            $em->flush();
            $this->get('session')->getFlashBag()->add('success', "L'auteur a été ajouté à la source");
        }
        catch(\Exception $e) {
            $this->get('session')->getFlashBag()->add('error', $e->getMessage());
        }
        return $this->redirect(
            $this->generateUrl(
                'account_source_edit',
                array(
                    "sourceType" => $source->getSourceType()->getName(),
                    "source" => $source->getId()
                )
            )
        );
    }

    public function deleteAction(Request $request, SourceAuthor $sourceAuthor) {
        $sm = $this->get('sourceml.source_manager');
        $em = $this->get('doctrine')->getManager();
        $source = $sourceAuthor->getSource();
        if(
                $sourceAuthor->getAuthor()->getUser()->getId() != $this->getUser()->getId()
            &&  !$sm->userCan("admin", $source)
        ) {
            throw new AccessDeniedException("your are not allowed to edit authors on this source");
        }
        $source->removeAuthor($sourceAuthor);
        $em->remove($sourceAuthor);
        try {
            $errors = $this->get('validator')->validate($source);
            if(count($errors) > 0) {
                throw new \Exception($errors[0]->getMessage());
            }
            $em->flush();
            $this->get('session')->getFlashBag()->add('success', "L'auteur a été enlevé de la source");
        }
        catch(\Exception $e) {
            $this->get('session')->getFlashBag()->add('error', $e->getMessage());
        }
        if($sm->userCan("edit", $source)) {
            return $this->redirect(
                $this->generateUrl(
                    'account_source_edit',
                    array(
                        "sourceType" => $source->getSourceType()->getName(),
                        "source" => $source->getId()
                    )
                )
            );
        }
        return $this->redirect(
            $this->generateUrl(
                'account_source_index',
                array(
                    "sourceType" => $source->getSourceType()->getName(),
                )
            )
        );
    }

    public function newAction(Request $request) {
        $em = $this->get('doctrine')->getManager();
        $sourceAuthorRepo = $em->getRepository(SourceAuthor::class);
        $sourceAuthors = $sourceAuthorRepo->getNotValidatedSourceAuthors($this->getUser());
        return $this->render(
            'Sources/Account/Source/new.html.twig',
            array(
                'sourceAuthors' => $sourceAuthors
            )
        );
    }

    public function acceptAction(Request $request, SourceAuthor $sourceAuthor) {
        $em = $this->get('doctrine')->getManager();
        $author = $sourceAuthor->getAuthor();
        if($author->getUser()->getId() != $this->getUser()->getId()) {
            throw new AccessDeniedException("your are not allowed to edit this author");
        }
        $sourceAuthor->setIsValid(true);
        try {
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success',
                $author->getName()
                ." est maintenant ".$sourceAuthor->getAuthorRole()->getName()
                ." sur ".$sourceAuthor->getSource()->getTitle()
            );
        }
        catch(\Exception $e) {
            $this->get('session')->getFlashBag()->add('error', $e->getMessage());
        }
        return $this->redirect($this->generateUrl('account_source_author_new'));
    }

    public function refuseAction(Request $request, SourceAuthor $sourceAuthor) {
        $em = $this->get('doctrine')->getManager();
        $author = $sourceAuthor->getAuthor();
        if($author->getUser()->getId() != $this->getUser()->getId()) {
            throw new AccessDeniedException("your are not allowed to edit this author");
        }
        $sourceAuthor->getSource()->removeAuthor($sourceAuthor);
        $em->remove($sourceAuthor);
        try {
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success',
                "L'accès ".$sourceAuthor->getAuthorRole()->getName()
                ." pour ".$author->getName()
                ." sur ".$sourceAuthor->getSource()->getTitle()
                ." a bien été refusé"
            );
        }
        catch(\Exception $e) {
            $this->get('session')->getFlashBag()->add('error', $e->getMessage());
        }
        return $this->redirect($this->generateUrl('account_source_author_new'));
    }

}
