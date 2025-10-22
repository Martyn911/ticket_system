<?php

namespace App\Tests;

use App\Booking\Application\Service\BookingService;
use App\Booking\Domain\Entity\Event;
use App\Booking\Domain\Repository\EventRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BookingControllerTest extends WebTestCase
{
    // Line 13: missingType.property error
    private KernelBrowser $client;

    private EntityManagerInterface $entityManager;

    private EventRepositoryInterface $eventRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        $this->eventRepository = static::getContainer()->get(EventRepositoryInterface::class);

        // Begin a transaction at the start of each test
        $this->entityManager->beginTransaction();
    }

    protected function tearDown(): void
    {
        // Roll back all DB changes after each test
        $this->entityManager->rollback();
        parent::tearDown();
    }

    private function createTestEvent(int $tickets): Event
    {
        $event = new Event('Test Concert', $tickets);
        $this->eventRepository->save($event);

        return $event;
    }

    /**
     * Test #1: Happy path (HTTP 202 + message enqueued).
     */
    public function testBookingSuccessful(): void
    {
        // 1. Create an event with 1 ticket
        $event = $this->createTestEvent(1);

        // 2. Make a request
        $this->client->request(
            'POST',
            '/api/events/'.$event->getId().'/book',
            [], [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"clientId": "'.$event->getId().'", "clientEmail": "test@example.com"}'
        );

        // 3. Verify the response
        $this->assertResponseStatusCodeSame(202); // HTTP 202 Accepted

        // 4. Verify that the message was sent to the queue
        $transport = static::getContainer()->get('messenger.transport.async');

        // Ensure there is exactly 1 message in the queue
        $this->assertCount(1, $transport->get());
    }

    /**
     * Test #2: Tickets sold out (HTTP 400).
     */
    public function testBookingFailsWhenNoTicketsLeft(): void
    {
        // 1. Create an event with 0 tickets
        $event = $this->createTestEvent(0);

        // 2. Make a request
        $this->client->request(
            'POST',
            '/api/events/'.$event->getId().'/book',
            [], [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"clientId": "'.$event->getId().'", "clientEmail": "test@example.com"}'
        );

        // 3. Verify the response (this is a DomainException)
        $this->assertResponseStatusCodeSame(400); // HTTP 400 Bad Request
        $this->assertStringContainsString('No tickets left', (string) $this->client->getResponse()->getContent());
    }

    /**
     * Test #3: Simulate a race condition (HTTP 409).
     */
    public function testBookingFailsOnRaceCondition(): void
    {
        // 1. Create an event with 1 ticket
        $event = $this->createTestEvent(1);

        // 2. SIMULATE A RACE CONDITION
        // We can't create a real race condition here, so we mock
        // the service to throw the desired error.

        $bookingServiceMock = $this->createMock(BookingService::class);
        $bookingServiceMock
            ->method('bookTicket') // When calling bookTicket...
            ->willThrowException(new OptimisticLockException('Mock lock exception', null)); // ...throw OptimisticLockException

        // Replace the real service with our mock
        static::getContainer()->set(BookingService::class, $bookingServiceMock);

        // 3. Make a request
        $this->client->request(
            'POST',
            '/api/events/'.$event->getId().'/book',
            [], [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"clientId": "'.$event->getId().'", "clientEmail": "test@example.com"}'
        );

        // 4. Verify the controller handled the error correctly
        $this->assertResponseStatusCodeSame(409); // HTTP 409 Conflict
        $this->assertStringContainsString('someone just booked the last ticket', (string) $this->client->getResponse()->getContent());
    }

    /**
     * Test #4: Invalid UUID in clientId -> 400.
     */
    public function testBookingFailsOnInvalidClientId(): void
    {
        $event = $this->createTestEvent(1);
        $this->client->request(
            'POST',
            '/api/events/'.$event->getId().'/book',
            [], [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"clientId": "not-a-uuid", "clientEmail": "test@example.com"}'
        );
        $this->assertResponseStatusCodeSame(400);
        $this->assertStringContainsString('errors', (string) $this->client->getResponse()->getContent());
    }

    /**
     * Test #5: Invalid email -> 400.
     */
    public function testBookingFailsOnInvalidEmail(): void
    {
        $event = $this->createTestEvent(1);
        $this->client->request(
            'POST',
            '/api/events/'.$event->getId().'/book',
            [], [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"clientId": "'.$event->getId().'", "clientEmail": "bad-email"}'
        );
        $this->assertResponseStatusCodeSame(400);
        $this->assertStringContainsString('errors', (string) $this->client->getResponse()->getContent());
    }
}
