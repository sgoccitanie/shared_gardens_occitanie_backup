<?php

namespace App\Controller\API;

use DateTime;
use DateTimeImmutable;
use Google\Service\Calendar as ServiceCalendar;
use Google_Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GoogleCalendarController extends AbstractController
{
    private ?ServiceCalendar $service = null;

    public function __construct(private readonly ParameterBagInterface $params)
    {
        $client = new \Google_Client();
        $credentialsPath = $this->params->get('google_application_credentials');

        if ($credentialsPath && file_exists($credentialsPath)) {
            try {
                $client->setAuthConfig($credentialsPath);
                $client->setApplicationName('semeursdejardins');
                $client->setScopes('https://www.googleapis.com/auth/calendar.readonly');
                $client->setSubject('rsj-23@rsj2025.iam.gserviceaccount.com');
                $client->setAccessType('offline');

                $guzzleClient = new \GuzzleHttp\Client(['curl' => [CURLOPT_SSL_VERIFYPEER => false]]);
                $client->setHttpClient($guzzleClient);

                $this->service = new ServiceCalendar($client);
            } catch (\Exception $e) {
                $this->service = null;
            }
        } else {
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
                'singleEvents' => true,
                'orderBy' => 'startTime',
                'timeZone' => 'Europe/Paris',
                'timeMin' => $dateNow->format(DateTime::RFC3339),
            ]);

            // Récupérer les items et filtrer les événements valides
            $eventsArray = $events->getItems();
            $calendarArray = $calendar->getItems();

            $filteredEvents = array_filter($eventsArray, function ($e) {
                return isset($e->start->dateTime) && isset($e->end->dateTime);
            });

            $filteredCalendar = array_filter($calendarArray, function ($e) {
                return isset($e->start->dateTime) && isset($e->end->dateTime);
            });

            return $this->render('google_calendar/index.html.twig', [
                'events' => $filteredEvents,
                'calendarList' => $filteredCalendar,
                'timeZone' => 'Europe/Paris',
            ]);
        } catch (\Exception $e) {
            return $this->render('google_calendar/index.html.twig', [
                'error' => 'Erreur lors de la récupération des événements : ' . $e->getMessage(),
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
            return new JsonResponse($event);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
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
                return isset($e->start->dateTime) && isset($e->end->dateTime);
            });

            if (empty($filteredEvents)) {
                return $this->render('google_calendar/coming.html.twig', [
                    'events' => [],
                    'error' => 'Aucun événement à venir.',
                ]);
            }

            return $this->render('google_calendar/coming.html.twig', [
                'events' => $filteredEvents,
            ]);
        } catch (\Exception $e) {
            return $this->render('google_calendar/coming.html.twig', [
                'events' => [],
                'error' => 'Erreur : ' . $e->getMessage(),
            ]);
        }
    }
}
