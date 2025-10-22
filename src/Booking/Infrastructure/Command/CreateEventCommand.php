<?php

namespace App\Booking\Infrastructure\Command;

use App\Booking\Domain\Entity\Event;
use App\Booking\Domain\ValueObject\EventId;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'app:create-event',
    description: 'Creates a new Event with a given name and total ticket count.'
)]
class CreateEventCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the event.')
            ->addArgument('tickets', InputArgument::REQUIRED, 'The total number of tickets available.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string $name */
        $name = $input->getArgument('name');
        /** @var int $tickets */
        $tickets = $input->getArgument('tickets');

        if ($tickets <= 0) {
            $io->error('The number of tickets must be greater than zero.');

            return Command::FAILURE;
        }

        try {
            // 1. Create Event (use the entity constructor for initialization)
            // We do not use EventId VO here because we need a string for Uuid::v4()
            $event = new Event(
                $name,
                $tickets
            );

            // 2. Persist via EntityManager
            $this->entityManager->persist($event);
            $this->entityManager->flush();

            $io->success(sprintf(
                'Successfully created event "%s" with %d total tickets. ID: %s',
                $name,
                $tickets,
                $event->getId() // Get ID for further use
            ));

            // Output the ID again to make it easy to copy for the ab test
            $output->writeln("\n<info>Event ID for testing:</info> <comment>{$event->getId()}</comment>\n");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('An error occurred while creating the event: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
