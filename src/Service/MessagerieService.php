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
        ?string $from = null
    ): bool {
        $fromAddress = $from ?? $this->defaultFrom;

        $mail = (new TemplatedEmail())
            ->from(new Address($fromAddress))
            ->to(new Address($to))
            ->subject($subject)
            ->replyTo(new Address($fromAddress))
            ->htmlTemplate($template)
            ->locale('fr')
            ->context($context);

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
        }
    }
}
