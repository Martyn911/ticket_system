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
    // Рядок 13: Помилка missingType.property
    private KernelBrowser $client;

    private EntityManagerInterface $entityManager;

    private EventRepositoryInterface $eventRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        $this->eventRepository = static::getContainer()->get(EventRepositoryInterface::class);

        // Починаємо транзакцію на початку кожного тесту
        $this->entityManager->beginTransaction();
    }

    protected function tearDown(): void
    {
        // Відкочуємо всі зміни в БД після кожного тесту
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
     * Тест №1: Щасливий шлях (HTTP 202 + Повідомлення в черзі).
     */
    public function testBookingSuccessful(): void
    {
        // 1. Створюємо подію з 1 квитком
        $event = $this->createTestEvent(1);

        // 2. Робимо запит
        $this->client->request(
            'POST',
            '/api/events/'.$event->getId().'/book',
            [], [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"clientId": "'.$event->getId().'", "clientEmail": "test@example.com"}'
        );

        // 3. Перевіряємо відповідь
        $this->assertResponseStatusCodeSame(202); // HTTP 202 Accepted

        // 4. Перевіряємо, що повідомлення потрапило в чергу
        $transport = static::getContainer()->get('messenger.transport.async');

        // Перевіряємо, що в черзі рівно 1 повідомлення
        $this->assertCount(1, $transport->get());
    }

    /**
     * Тест №2: Квитки закінчилися (HTTP 400).
     */
    public function testBookingFailsWhenNoTicketsLeft(): void
    {
        // 1. Створюємо подію з 0 квитками
        $event = $this->createTestEvent(0);

        // 2. Робимо запит
        $this->client->request(
            'POST',
            '/api/events/'.$event->getId().'/book',
            [], [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"clientId": "'.$event->getId().'", "clientEmail": "test@example.com"}'
        );

        // 3. Перевіряємо відповідь (це DomainException)
        $this->assertResponseStatusCodeSame(400); // HTTP 400 Bad Request
        $this->assertStringContainsString('No tickets left', (string) $this->client->getResponse()->getContent());
    }

    /**
     * Тест №3: Симуляція Race Condition (HTTP 409).
     */
    public function testBookingFailsOnRaceCondition(): void
    {
        // 1. Створюємо подію з 1 квитком
        $event = $this->createTestEvent(1);

        // 2. СИМУЛЮЄМО RACE CONDITION
        // Ми не можемо створити її по-справжньому, тому ми "мокаємо"
        // сервіс, щоб він згенерував потрібну нам помилку.

        $bookingServiceMock = $this->createMock(BookingService::class);
        $bookingServiceMock
            ->method('bookTicket') // При виклику методу bookTicket...
            ->willThrowException(new OptimisticLockException('Mock lock exception', null)); // ...кинь помилку OptimisticLock

        // Замінюємо справжній сервіс на наш мок
        static::getContainer()->set(BookingService::class, $bookingServiceMock);

        // 3. Робимо запит
        $this->client->request(
            'POST',
            '/api/events/'.$event->getId().'/book',
            [], [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"clientId": "'.$event->getId().'", "clientEmail": "test@example.com"}'
        );

        // 4. Перевіряємо, що контролер правильно обробив цю помилку
        $this->assertResponseStatusCodeSame(409); // HTTP 409 Conflict
        $this->assertStringContainsString('someone just booked the last ticket', (string) $this->client->getResponse()->getContent());
    }

    /**
     * Тест №4: Некоректний UUID у clientId -> 400.
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
     * Тест №5: Некоректний email -> 400.
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
