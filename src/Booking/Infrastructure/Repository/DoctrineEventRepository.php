<?php

namespace App\Booking\Infrastructure\Repository;

use App\Booking\Domain\Entity\Event;
use App\Booking\Domain\Repository\EventRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class DoctrineEventRepository implements EventRepositoryInterface
{
    private EntityManagerInterface $em;

    /**
     * @var EntityRepository
     */
    private $entityRepository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
        // Створюємо "помічника" для легкого пошуку
        $this->entityRepository = $this->em->getRepository(Event::class);
    }

    public function find(string $id): ?Event
    {
        return $this->entityRepository->find($id);
    }

    public function save(Event $event): void
    {
        $this->em->persist($event);
        $this->em->flush();
    }
}
