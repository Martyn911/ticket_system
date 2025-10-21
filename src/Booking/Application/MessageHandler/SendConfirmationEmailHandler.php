<?php

namespace App\Booking\Application\MessageHandler;

use App\Booking\Application\Message\SendConfirmationEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
class SendConfirmationEmailHandler
{
    public function __construct(private MailerInterface $mailer)
    {
    }

    public function __invoke(SendConfirmationEmail $message): void
    {
        // Імітуємо повільну роботу
        sleep(5);

        $email = (new Email())
            ->from('no-reply@tickets.com')
            ->to($message->clientEmail)
            ->subject('Your ticket is confirmed!')
            ->text(sprintf(
                'Congrats! Your booking %s is confirmed. Your ticket number is %d.',
                $message->bookingId,
                $message->ticketNumber
            ));

        $this->mailer->send($email);
    }
}
