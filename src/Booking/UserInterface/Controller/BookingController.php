<?php

namespace App\Booking\UserInterface\Controller;

use App\Booking\Application\Command\BookTicketCommand;
use App\Booking\Application\Service\BookingService;
use App\Booking\Domain\Exception\EventNotFound;
use App\Booking\Domain\Exception\TicketsSoldOut;
use App\Booking\UserInterface\Dto\BookTicketRequest;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BookingController extends AbstractController
{
    #[Route('/api/events/{eventId}/book', methods: ['POST'])]
    public function book(string $eventId, Request $request, BookingService $bookingService, ValidatorInterface $validator): Response
    {
        $data = $request->toArray();
        $dto = new BookTicketRequest(
            eventId: $eventId,
            clientId: $data['clientId'] ?? '',
            clientEmail: $data['clientEmail'] ?? ''
        );

        $violations = $validator->validate($dto);
        if (\count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = [
                    'field' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ];
            }

            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $command = new BookTicketCommand(
            $dto->eventId,
            $dto->clientId,
            $dto->clientEmail
        );

        try {
            $bookingService->bookTicket($command);

            return $this->json([
                'status' => 'booking_accepted',
                'message' => 'Your booking is being processed.',
            ], Response::HTTP_ACCEPTED);
        } catch (EventNotFound $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (TicketsSoldOut $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (OptimisticLockException $e) {
            // Ось тут ми ловимо RACE CONDITION!
            return $this->json(
                ['error' => 'Sorry, someone just booked the last ticket. Please try again.'],
                Response::HTTP_CONFLICT // 409 Conflict
            );
        }
    }
}
