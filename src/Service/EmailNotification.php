<?php

namespace App\Service;

use App\Entity\CaseFile;
use App\Entity\Nutzer;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Predefined email notifications.
 *
 * @author Ben Brooksnieder
 */
class EmailNotification
{
    public function __construct(
        private MailerInterface $mailer,
        private EntityManagerInterface $entityManager,
        private TranslatorInterface $translator,
        private UrlGeneratorInterface $urlGenerator,
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

        $link = $this->urlGenerator->generate('detail_case', [
            'id' => $case->getCaseId(),
        ]);

        foreach ($subscribers as $subscriber) {
            $subject = $this->translator->trans('email.case_creation.subject', ['case' => $case->getCaseId()], locale: $subscriber->getLanguage());

            $message = (new TemplatedEmail())
            ->subject($subject)
            ->from($_ENV["mailer_resetting_host"])
            ->to($subscriber->getEmail());

            $message->htmlTemplate('emails/notifyCaseCreation.html.twig');
            $message->context([
                'recipient' => $subscriber->getFullname(),
                'user' => $user->getFullname(),
                'case' => $case->getCaseId(),
                'link' => $link,
                'user_locale' => $user->getLanguage(),
            ]);

            $this->mailer->send($message);
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

        $link = $this->urlGenerator->generate('detail_case', [
            'id' => $case->getCaseId(),
        ]);

        foreach ($subscribers as $subscriber) {
            $subject = $this->translator->trans('email.case_alteration.subject', ['case' => $case->getCaseId()], locale: $subscriber->getLanguage());

            $message = (new TemplatedEmail())
            ->subject($subject)
            ->from($_ENV["mailer_resetting_host"])
            ->to($subscriber->getEmail());

            $message->htmlTemplate('emails/notifyCaseAlteration.html.twig');
           $message->context([
                'recipient' => $subscriber->getFullname(),
                'user' => $user->getFullname(),
                'case' => $case->getCaseId(),
                'link' => $link,
                'user_locale' => $user->getLanguage(),
            ]);

            $this->mailer->send($message);
        }
    }
}
