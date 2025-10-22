<?php

namespace App\Booking\Application\Service;

use App\Booking\Application\Command\BookTicketCommand;
use App\Booking\Application\Message\SendConfirmationEmail;
use App\Booking\Domain\Entity\Booking;
use App\Booking\Domain\Exception\EventNotFound;
use App\Booking\Domain\Repository\EventRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class BookingService
{
    public function __construct(
        private EntityManagerInterface $em,
        private EventRepositoryInterface $eventRepository,
        private MessageBusInterface $bus,
    ) {
    }

    /**
     * @throws EventNotFound
     * @throws OptimisticLockException
     */
    public function bookTicket(BookTicketCommand $command): void
    {
        // This method runs everything inside a transaction
        // If an error occurs inside (including OptimisticLockException),
        // the transaction will be rolled back.
        $this->em->wrapInTransaction(function () use ($command) {
            // 1. Find the event
            $event = $this->eventRepository->find($command->eventId);
            if (!$event) {
                throw new EventNotFound('Event not found.');
            }

            // 2. Invoke domain logic
            // TicketsSoldOut may be thrown here
            // if tickets are sold out.
            $ticket = $event->bookTicket($command->clientId);

            // 3. Create Booking entity
            $booking = new Booking(
                Uuid::v4(),
                Uuid::fromString($command->clientId),
                Uuid::fromString($command->eventId),
                $ticket->getNumber()
            );

            // 4. Persist everything
            // The EntityManager will persist both Event and Booking
            $this->em->persist($booking);
            $this->em->persist($event);
            // $this->em->flush() will be called automatically

            // 5. Send a message to RabbitMQ (after the transaction succeeds)
            $this->bus->dispatch(new SendConfirmationEmail(
                $booking->getId()->toRfc4122(),
                $command->clientEmail,
                $booking->getTicketNumber()
            ));
        });
    }
}
