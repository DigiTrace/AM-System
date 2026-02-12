<?php

namespace App\Service;

use App\Entity\CaseFile;
use App\Entity\Nutzer;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Predefined email notifications.
 *
 * @author Ben Brooksnieder
 */
class EmailNotification
{
    public function __construct(
        private TransportInterface $transport,
        private EntityManagerInterface $entityManager,
        private TranslatorInterface $translator,
        private LoggerInterface $logger
    ){}

    public function notifyCaseCreation(CaseFile $case, Nutzer $user)
    {
        $subscribers = $this->entityManager->getRepository(Nutzer::class)->findBy([
            'notifyCaseCreation' => true,
        ]);

        $this->logger->info('Send "notifyCaseCreation" email to %count subscribers', [
            'count' => \count($subscribers)
        ]);

        foreach ($subscribers as $subscriber) {
            $subject = $this->translator->trans('email_case_was_created_subject', locale: $subscriber->getLanguage());

            $message = (new TemplatedEmail())
            ->subject($subject)
            ->from($_ENV["mailer_resetting_host"])
            ->to($subscriber->getEmail());

            $message->htmlTemplate('emails/notifyCaseCreation.html.twig');
            $message->context([
                'name' => $subscriber->getFullname(),
                'calleduser' => $user->getFullname(),
                'caseid' => $case->getCaseId(),
                'user_locale' => $user->getLanguage(),
            ]);

            $this->transport->send($message);
        }
    }

    public function notifyCaseAlteration(CaseFile $case, Nutzer $user)
    {
        $subscribers = $this->entityManager->getRepository(Nutzer::class)->findBy([
            'notifyCaseCreation' => true,
        ]);

        $this->logger->info('Send "notifyCaseAlteration" email to %count subscribers', [
            'count' => \count($subscribers)
        ]);

        foreach ($subscribers as $subscriber) {
            $subject = $this->translator->trans('email_case_was_altered_subject', locale: $subscriber->getLanguage());

            $message = (new TemplatedEmail())
            ->subject($subject)
            ->from($_ENV["mailer_resetting_host"])
            ->to($subscriber->getEmail());

            $message->htmlTemplate('emails/notifyCaseAlteration.html.twig');
            $message->context([
                'name' => $subscriber->getFullname(),
                'calleduser' => $user->getFullname(),
                'caseid' => $case->getCaseId(),
                'user_locale' => $user->getLanguage(),
            ]);

            $this->transport->send($message);
        }
    }
}
