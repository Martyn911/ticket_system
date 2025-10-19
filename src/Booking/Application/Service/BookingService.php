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
        private MessageBusInterface $bus
    ) {}

    /**
     * @throws EventNotFound
     * @throws OptimisticLockException
     */
    public function bookTicket(BookTicketCommand $command): void
    {
        // Ця функція запускає все в транзакції
        // Якщо всередині станеться помилка (включно з OptimisticLockException),
        // транзакція відкотиться.
        $this->em->wrapInTransaction(function() use ($command) {

            // 1. Знаходимо подію
            $event = $this->eventRepository->find($command->eventId);
            if (!$event) {
                throw new EventNotFound('Event not found.');
            }

            // 2. Викликаємо доменну логіку
            // Тут може викинутись TicketsSoldOut,
            // якщо квитки закінчились.
            $ticket = $event->bookTicket($command->clientId);

            // 3. Створюємо сутність Booking
            $booking = new Booking(
                Uuid::v4(),
                Uuid::fromString($command->clientId),
                Uuid::fromString($command->eventId),
                $ticket->getNumber()
            );

            // 4. Зберігаємо все
            // EntityManager збереже і Event, і Booking
            $this->em->persist($booking);
            $this->em->persist($event);
            // $this->em->flush() буде викликано автоматично

            // 5. Відправляємо повідомлення в RabbitMQ (після успіху транзакції)
            $this->bus->dispatch(new SendConfirmationEmail(
                $booking->getId()->toRfc4122(),
                $command->clientEmail,
                $booking->getTicketNumber()
            ));
        });
    }
}
