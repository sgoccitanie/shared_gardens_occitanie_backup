<?php

namespace App\Controller\API;

use DateTime;
use DateTimeImmutable;
use Google\Service\Calendar as ServiceCalendar;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Service\CommonDataService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;

class GoogleCalendarController extends AbstractController
{
    private ?ServiceCalendar $service = null;

    public function __construct(private readonly CommonDataService $commonDataService, private readonly ParameterBagInterface $params, private readonly LoggerInterface $logger)
    {
        $client = new \Google_Client();
        $credentialsPath = $this->params->get('google_application_credentials');

        if ($credentialsPath && file_exists($credentialsPath)) {
            try {
                $client->setAuthConfig($credentialsPath);
                $client->setApplicationName('semeursdejardins');
                $client->setScopes('https://www.googleapis.com/auth/calendar.readonly');
                // $client->setSubject('semeursdejardinslr@gmail.com');
                $client->setAccessType('offline');

                $this->service = new ServiceCalendar($client);
            } catch (\Exception $e) {
                $this->logger->error('Google Calendar error: ' . $e->getMessage());
                $this->service = null;
            }
        } else {
            $this->logger->error("Fichier de credentials introuvable. Chemin testé : " . ($credentialsPath ?? 'null'));
            $this->service = null;
        }
    }

    #[Route('/google/calendar/', name: 'app_google_calendar')]
    public function index(): Response
    {
        if ($this->service === null) {
            return $this->render('google_calendar/index.html.twig', [
                'error' => 'Le service Google Calendar n\'est pas disponible.',
                'events' => [],
                'calendarList' => [],
            ]);
        }

        try {
            $dateNow = new DateTimeImmutable('now');
            $dateNowPlusOneWeek = $dateNow->modify('+1 week');

            $events = $this->service->events->listEvents('semeursdejardinslr@gmail.com', [
                'orderBy' => 'startTime',
                'singleEvents' => true,
                'timeMin' => $dateNow->format(DateTime::RFC3339),
                'timeMax' => $dateNowPlusOneWeek->format(DateTime::RFC3339),
                'timeZone' => 'Europe/Paris',
            ]);



            $calendar = $this->service->events->listEvents('semeursdejardinslr@gmail.com', [
                'maxResults' => 2500,           // Augmenter la limite (max autorisé)
                'singleEvents' => true,
                'orderBy' => 'startTime',
                'timeZone' => 'Europe/Paris',
                'timeMin' => $dateNow->modify('-1 year')->format(DateTime::RFC3339),  // Passés
                'timeMax' => $dateNow->modify('+2 years')->format(DateTime::RFC3339), // Futurs
            ]);
            // Récupérer les items et filtrer les événements valides
            $eventsArray = $events->getItems();
            $calendarArray = $calendar->getItems();

            $filteredEvents = array_filter($eventsArray, function ($e) {
                return (isset($e->start->dateTime) && isset($e->end->dateTime))
                    || (isset($e->start->date) && isset($e->end->date));
            });

            // Retirer la description de chaque événement et les noms de calendrier entre parenthèses RGPD
            foreach ($filteredEvents as $event) {
                if ($event->summary) {
                    $event->summary = preg_replace('/\s*\([^)]*\)/', '', $event->summary);
                }
                if ($event->description) {
                    // Retirer les emails
                    // Retire tout entre "<b>Réservé par</b>" et "<b>Ville</b>"
                    $event->description = preg_replace(
                        '/<b>Réservé par<\/b>.*?(?=<b>Ville<\/b>|$)/s',
                        '',
                        $event->description
                    );
                }
            }
            // Filtre qui inclut aussi les événements "journée entière"
            $filteredCalendar = array_filter($calendarArray, function ($e) {
                return (isset($e->start->dateTime) && isset($e->end->dateTime))
                    || (isset($e->start->date) && isset($e->end->date));
            });
            // Retirer la description de chaque événement et les noms de calendrier entre parenthèses RGPD
            foreach ($filteredCalendar as $event) {
                // Nettoyage RGPD
                if ($event->summary) {
                    $event->summary = preg_replace('/\s*\([^)]*\)/', '', $event->summary);
                }
                if ($event->description) {
                    // Retire tout entre "<b>Réservé par</b>" et "<b>Ville</b>"
                    $event->description = preg_replace(
                        '/<b>Réservé par<\/b>.*?(?=<b>Ville<\/b>|$)/s',
                        '',
                        $event->description
                    );
                }
            }

            return $this->render('google_calendar/index.html.twig', [
                'events' => $filteredEvents,
                'calendarList' => $filteredCalendar,
                'timeZone' => 'Europe/Paris',
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des événements Google Calendar : ' . $e->getMessage());
            return $this->render('google_calendar/index.html.twig', [
                'error' => 'Erreur lors de la récupération des événements.',
                'events' => [],
                'calendarList' => [],
            ]);
        }
    }

    #[Route('/google/calendar/event/{id}', name: 'app_google_calendar_event')]
    public function event($id): JsonResponse
    {
        if ($this->service === null) {
            return new JsonResponse(['error' => 'Service non disponible'], 500);
        }

        try {
            $event = $this->service->events->get('semeursdejardinslr@gmail.com', $id);

            // Nettoyage RGPD
            if ($event->summary) {
                $event->summary = preg_replace('/\s*\([^)]*\)/', '', $event->summary);
            }
            if ($event->description) {
                // Retire tout entre "<b>Réservé par</b>" et "<b>Ville</b>"
                $event->description = preg_replace(
                    '/<b>Réservé par<\/b>.*?(?=<b>Ville<\/b>|$)/s',
                    '',
                    $event->description
                );
            }

            return new JsonResponse($event);
        } catch (\Exception $e) {
            $this->logger->error('Erreur récupération événement Google Calendar', [
                'event_id' => $id,
                'exception' => $e,
            ]);

            return new JsonResponse(['error' => 'Événement indisponible'], 500);
        }
    }

    #[Route('/google/calendar/coming-soon', name: 'app_coming_soon')]
    public function comingSoon(): Response
    {
        if ($this->service === null) {
            return $this->render('google_calendar/coming.html.twig', [
                'events' => [],
            ]);
        }

        try {
            $dateNow = new DateTimeImmutable('now');
            $dateNowPlusOneWeek = $dateNow->modify('+1 week');

            $events = $this->service->events->listEvents('semeursdejardinslr@gmail.com', [
                'maxResults' => 10,
                'orderBy' => 'startTime',
                'singleEvents' => true,
                'timeMin' => $dateNow->format(DateTime::RFC3339),
                'timeMax' => $dateNowPlusOneWeek->format(DateTime::RFC3339),
                'timeZone' => 'Europe/Paris',
            ]);

            // Récupérer les items et filtrer
            $eventsArray = $events->getItems();

            $filteredEvents = array_filter($eventsArray, function ($e) {
                return (isset($e->start->dateTime) && isset($e->end->dateTime))
                    || (isset($e->start->date) && isset($e->end->date));
            });
            // Retirer la description de chaque événement et les noms de calendrier entre parenthèses RGPD
            foreach ($filteredEvents as $event) {
                if ($event->summary) {
                    // Retire tout entre "<b>Réservé par</b>" et "<b>Ville</b>"
                    $event->description = preg_replace(
                        '/<b>Réservé par<\/b>.*?(?=<b>Ville<\/b>|$)/s',
                        '',
                        $event->description
                    );
                    $event->description = strip_tags($event->description, '<b><br><p><strong><em>');
                }
            }

            if (empty($filteredEvents)) {
                $this->logger->info('Aucun événement à venir trouvé dans Google Calendar.');
                return $this->render('google_calendar/coming.html.twig', [
                    'events' => [],
                    'error' => 'Aucun événement à venir.',
                ]);
            }

            return $this->render('google_calendar/coming.html.twig', [
                'events' => $filteredEvents,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur récupération événements à venir Google Calendar : ' . $e->getMessage());
            return $this->render('google_calendar/coming.html.twig', [
                'events' => [],
                'error' => 'Erreur : Impossible de récupérer les événements à venir.',
            ]);
        }
    }
}
