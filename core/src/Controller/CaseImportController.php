<?php

namespace App\Controller;

use App\Command\CommandDispatcherTrait;
use App\Command\Import\ImportCaseJsonCommand;
use App\Entity\User\User;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class CaseImportController extends AbstractController
{
    use CommandDispatcherTrait;

    /**
     * @Route("/salt/case/import", name="import_case_file")
     * @Security("is_granted('create', 'lsdoc')")
     */
    public function importAction(Request $request, UserInterface $user): JsonResponse
    {
        if (!$user instanceof User) {
            return new JsonResponse(['error' => ['message' => 'Invalid user']], Response::HTTP_UNAUTHORIZED);
        }

        $content = base64_decode($request->request->get('fileContent'));

        $command = new ImportCaseJsonCommand($content, $user->getOrg(), $user);
        $this->sendCommand($command);

        return new JsonResponse([
            'message' => 'Success',
        ]);
    }

    /**
     * @Route("/salt/case/importRemote", name="import_case_file_remote")
     * @Security("is_granted('create', 'lsdoc')")
     */
    public function importRemoteAction(Request $request, UserInterface $user, ClientInterface $guzzleJsonClient): Response
    {
        if (!$user instanceof User) {
            return new JsonResponse(['error' => ['message' => 'Invalid user']], Response::HTTP_UNAUTHORIZED);
        }

        $defaultData = [];
        $form = $this->createFormBuilder($defaultData)
            ->add('url', UrlType::class, [
                'constraints' => new NotBlank(),
            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            ini_set('memory_limit', '2G');
            set_time_limit(300);

            $data = $form->getData();

            try {
                $response = $guzzleJsonClient->request(
                    'GET',
                    $data['url'],
                    [
                        RequestOptions::AUTH => null,
                        RequestOptions::ALLOW_REDIRECTS => true,
                        RequestOptions::TIMEOUT => 300,
                        RequestOptions::HEADERS => [
                            'Accept' => 'application/json, text/plain, */*;q=0.5',
                        ],
                        RequestOptions::HTTP_ERRORS => false,
                    ]
                );
            } catch (\Exception $e) {
                return new JsonResponse(['error' => ['url' => $data['url'], 'exception' => $e->getMessage()]]);
            }

            if (200 !== $response->getStatusCode()) {
                return new JsonResponse(['error' => ['url' => $data['url'], 'response_code' => $response->getStatusCode(), 'response_reason' => $response->getReasonPhrase()]]);
            }

            $content = $response->getBody()->getContents();

            $command = new ImportCaseJsonCommand($content, $user->getOrg(), $user);
            $this->sendCommand($command);

            return new JsonResponse(['message' => 'Success']);
        }

        return $this->render('framework/import/new.html.twig', ['form' => $form->createView()]);
    }
}
