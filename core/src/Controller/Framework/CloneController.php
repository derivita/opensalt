<?php

namespace App\Controller\Framework;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\CloneFrameworkCommand;
use App\Entity\Framework\LsDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Copy controller.
 *
 * @Route("/clone")
 */
class CloneController extends AbstractController
{
    use CommandDispatcherTrait;

    /**
     * @Route("/framework/{id}", name="clone_framework", methods={"GET"})
     * @Security("is_granted('edit', lsDoc) and is_granted('create', 'lsdoc')")
     */
    public function frameworkAction(Request $request, LsDoc $lsDoc): Response
    {
        $command = new CloneFrameworkCommand($lsDoc);
        $this->sendCommand($command);
        $newLsDoc = $command->getNotificationEvent()->getDoc();

        return $this->redirectToRoute('doc_tree_view', ['slug' => $newLsDoc->getId(), 'edit' => 1]);
    }
}
