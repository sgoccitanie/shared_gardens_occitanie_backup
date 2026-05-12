<?php

namespace App\Controller;

use App\Form\ContactPageFormType;
use App\Repository\AssociationRepository;
use App\Repository\SubjectEmailRepository;
use App\Service\HeaderService;
use App\Service\MessagerieService;
use App\Service\Utils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Routing\Attribute\Route;

final class ContactController extends AbstractController
{
    private $mailer;
    private $transport;

    public function __construct(
        MailerInterface $mailer,
        TransportInterface $transport,
        private HeaderService $headerService,
        private AssociationRepository $assoRepo
    ) {
        $this->mailer = $mailer;
        $this->transport = $transport;
    }

    #[Route('/contact', name: 'app_contact')]
    public function index(MessagerieService $messagerieService, Request $request, SubjectEmailRepository $subjectRepository, AssociationRepository $associationRepository): Response
    {
        $form = $this->createForm(ContactPageFormType::class);
        $form->handleRequest($request);
        $success = false;

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // get data from select
                $recaptcha = $request->request->get('g-recaptcha-response');
                $recaptchaSecret = '6Lf_274qAAAAAJpF7Vyd7zsvZwGO_MbXeEJ5sljC';
                $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$recaptchaSecret}&response={$recaptcha}");
                $responseKeys = json_decode($response, true);
                if (intval($responseKeys["success"]) !== 1) {
                    $this->addFlash('error', 'Veuillez confirmer que vous n\'êtes pas un robot.');
                    return $this->render('home/contact.html.twig', [
                        'form' => $form->createView(),
                        'success' => $success,
                    ]);
                }
                $dataSelect = $form->get('id')->getData();
                $id = $dataSelect->getId();
                $object = $dataSelect->getLabel();
                $associationId = 17;
                // get subject from id
                try {
                    $subject = $subjectRepository->findOneBy(['id' => $id]);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Veuillez sélectionner un sujet.');
                    return $this->render('home/contact.html.twig', [
                        'form' => $form->createView(),
                        'success' => $success,
                    ]);
                }
                if ($object != $subject->getLabel()) {
                    $this->addFlash('error', 'Veuillez sélectionner un sujet correct.');
                    return $this->render('home/contact.html.twig', [
                        'form' => $form->createView(),
                        'success' => $success,
                    ]);
                }
                // get data from other fields
                $name = $form->get('name')->getData();
                $email = $form->get('email')->getData();
                $message = $form->get('message')->getData();

                if ($name == null || $email == null || $message == null || $id == null || $object == null || $associationId == null) {
                    $this->addFlash('error', 'Veuillez remplir tous les champs.');
                } else {
                    // clean data
                    $id = Utils::cleanInputStatic($id);
                    $associationId = Utils::cleanInputStatic($associationId);
                    $name = Utils::cleanInputStatic($name);
                    $email = Utils::cleanInputStatic($email);
                    $message = Utils::cleanInputStatic($message);
                    // get association email
                    $associationEmail = $associationRepository->findOneBy(['id' => $associationId])->getEmail();
                    $messagerieService->sendMail($object, $associationEmail, 'email/contact_email.html.twig', [
                        'name' => $name,
                        'object' => $object,
                        'message' => $message,
                    ], $email);
                    if ($messagerieService) {
                        $this->addFlash('success', 'Votre message a été envoyé avec succès');
                        $success = true;
                    } else {
                        $this->addFlash('error', 'Votre message n\'a pas pu être envoyé');
                    }
                }
            } else {
                $errors = $form->getErrors(true, false);
                $this->addFlash('error', $errors);
            }
        }

        // Récupérer l'ID de l'association active
        $firstAssociation = $this->assoRepo->findOneBy([], ['id' => 'ASC']);
        $assoId = $firstAssociation ? $firstAssociation->getId() : 1;

        // Récupérer les données de l'association pour le header
        $headerData = $this->headerService->getHeaderData($assoId);
        $logoPath = $this->headerService->getLogoPath($headerData['assoLogo']);
        $formattedMantra = $this->headerService->getFormattedMantra($headerData['assoMantra']);

        return $this->render('home/contact.html.twig', [
            'form' => $form->createView(),
            'success' => $success,
            'logoPath' => $logoPath,
            'headerData' => $headerData,
            'formattedMantra' => $formattedMantra,
        ]);
    }
}
