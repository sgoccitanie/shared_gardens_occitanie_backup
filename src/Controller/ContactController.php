<?php

namespace App\Controller;

use App\Form\ContactPageFormType;
use App\Repository\AssociationRepository;
use App\Repository\SubjectEmailRepository;
use App\Service\CommonDataService;
use App\Service\MessagerieService;
use App\Service\Utils;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class ContactController extends AbstractController
{

    public function __construct(
        private readonly CommonDataService $commonDataService,
        private readonly AssociationRepository $assoRepo,
        private readonly LoggerInterface $contactLogger,
        #[Autowire('%env(RECAPTCHA_SECRET_KEY)%')]
        private readonly string $recaptchaSecret,
    ) {}

    #[Route('/contact', name: 'app_contact')]
    public function index(
        #[Autowire(service: 'limiter.contact_form')]
        RateLimiterFactory $contactLimiter,
        MessagerieService $messagerieService,
        Request $request,
        SubjectEmailRepository $subjectRepository,
    ): Response {
        $form = $this->createForm(ContactPageFormType::class);
        $form->handleRequest($request);
        $success = false;

        if ($form->isSubmitted()) {
            //create a limiter for the contact form with a limit of 5 requests per hour per IP address
            $limiter = $contactLimiter->create($request->getClientIp());
            if (false === $limiter->consume(1)->isAccepted()) {
                $this->contactLogger->warning('Trop de requêtes sur le formulaire de contact', [
                    'ip' => $request->getClientIp(),
                ]);
                $this->addFlash('error', 'Trop de tentatives, veuillez patienter avant de réessayer.');
                return $this->redirectToRoute('app_contact');
            }
            if ($form->isValid()) {
                // get data from select
                if ($_ENV['APP_ENV'] !== 'dev') {
                    $recaptcha = $request->request->get('g-recaptcha-response');
                    $recaptchaSecret = $this->recaptchaSecret;
                    $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$recaptchaSecret}&response={$recaptcha}");
                    $responseKeys = json_decode($response, true);
                    if (intval($responseKeys["success"]) !== 1) {
                        $this->addFlash('error', 'Veuillez confirmer que vous n\'êtes pas un robot.');
                        return $this->render('home/contact.html.twig', [
                            'form' => $form->createView(),
                            'success' => $success,
                        ]);
                    }
                }
                $dataSelect = $form->get('subject')->getData();
                if (!$dataSelect) {
                    $this->addFlash('error', 'Veuillez sélectionner un sujet.');
                    return $this->redirectToRoute('app_contact');
                }
                $id = $dataSelect->getId();
                $object = $dataSelect->getLabel();


                // Validation ID
                if (!is_numeric($id) || (int)$id <= 0) {
                    $this->addFlash('error', 'Sujet invalide.');
                    return $this->redirectToRoute('app_contact');
                }

                // Association destinataire
                $firstAssociation = $this->assoRepo->findOneBy([], ['id' => 'ASC']);
                if (!$firstAssociation) {
                    $this->contactLogger->error('Page contact - tentative envoi email - erreur : aucune association trouvée');
                    $this->addFlash('error', 'Erreur technique. Veuillez réessayer plus tard.');
                    return $this->redirectToRoute('app_contact');
                }


                $subject = $subjectRepository->findOneBy(['id' => $id]);
                if (!$subject || $object !== $subject->getLabel()) {
                    $this->addFlash('error', 'Sujet du mail invalide. Sélectionnez un sujet valide.');
                    return $this->redirectToRoute('app_contact');
                }
                // get data from other fields
                $name = Utils::cleanInputStatic($form->get('name')->getData());
                $email = Utils::cleanInputStatic($form->get('email')->getData());
                $message = Utils::cleanInputStatic($form->get('message')->getData());


                // Envoi email
                $emailSent = $messagerieService->sendMail(
                    $object,
                    "julie.barn9@gmail.com",
                    'email/contact_email.html.twig',
                    [
                        'name' => $name,
                        'object' => $object,
                        'message' => $message,
                    ],
                    $email
                );

                if ($emailSent) {
                    $this->addFlash('success', 'Votre message a été envoyé avec succès');
                    $success = true;
                } else {
                    $this->contactLogger->error('Échec envoi message de contact', [
                        'from' => $email,
                        'subject' => $object,
                    ]);
                    $this->addFlash('error', 'Votre message n\'a pas pu être envoyé. Veuillez réessayer.');
                }
            } else {
                foreach ($form->getErrors(true) as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            }
        }

        $headerData = $this->commonDataService->getFullHeaderData();

        return $this->render('home/contact.html.twig', array_merge($headerData, [
            'form' => $form->createView(),
            'success' => $success,
        ]));
    }
}
