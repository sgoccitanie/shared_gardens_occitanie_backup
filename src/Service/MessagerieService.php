<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class MessagerieService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        #[Autowire('%env(MAILER_FROM)%')]
        private readonly string $defaultFrom,
    ) {}

    public function sendMail(
        string $subject,
        string $to,
        string $template,
        array $context,
        ?string $replyTo = null
    ): bool {
        $mail = (new TemplatedEmail())
            ->from(new Address($this->defaultFrom))  // toujours Gmail
            ->to(new Address($to, 'Réseau des Semeurs de Jardins'))
            ->subject($subject)
            ->htmlTemplate($template)
            ->locale('fr')
            ->context($context);

        // Si un replyTo est fourni (pour formulaire contact)
        if ($replyTo) {
            $mail->replyTo(new Address($replyTo));
        }
        dump('emailSent =', $mail);
        try {
            $this->mailer->send($mail);
            $this->logger->info('Email envoyé avec succès', [
                'to' => $to,
                'subject' => $subject,
            ]);
            return true;
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Erreur envoi email: ' . $e->getMessage(), [
                'to' => $to,
                'subject' => $subject,
            ]);
            return false;
        } catch (\Exception $e) {
            $this->logger->error('Erreur inattendue: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }
}
