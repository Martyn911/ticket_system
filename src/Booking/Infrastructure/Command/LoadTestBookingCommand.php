<?php

namespace App\Booking\Infrastructure\Command;

use App\Booking\Domain\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:load-test:booking',
    description: 'Runs Apache Benchmark (ab) to stress-test the booking endpoint.'
)]
class LoadTestBookingCommand extends Command
{
    private const HOST = 'http://nginx';

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Starting Load Test for Booking Endpoint...');

        $testEvent = new Event(
            'Load Test Event',
            1
        );
        $this->entityManager->persist($testEvent);
        $this->entityManager->flush();
        $io->note('Created new test event with ID: '.$testEvent->getId());

        // 1. Create a temporary JSON file
        $jsonFilePath = sys_get_temp_dir().'/ab_post_data.json';
        file_put_contents($jsonFilePath, '{"clientId": "'.$testEvent->getId().'", "clientEmail": "test@example.com"}');

        // 2. Build the ab command
        // Use Docker service names (nginx) and the internal port (80)
        $url = self::HOST.'/api/events/'.$testEvent->getId().'/book';

        // Command: 10 requests (-n) with 10 concurrent clients (-c)
        $command = [
            'ab',
            '-n', '10',
            '-c', '10',
            '-p', $jsonFilePath,
            '-T', 'application/json',
            $url,
        ];

        // 3. Execute the command via Symfony Process
        $process = new Process($command);
        $process->setTimeout(60); // Allow 60 seconds to complete

        $output->writeln('Executing: '.$process->getCommandLine());
        $process->run();

        // 4. Handle the result
        if (!$process->isSuccessful()) {
            $io->error("ab command failed. Is 'ab' installed in the php-fpm container? (See Dockerfile)");
            $io->text($process->getErrorOutput());

            return Command::FAILURE;
        }

        // 5. Output the result
        $io->success('ab test finished successfully. Analyzing results...');
        $output->writeln($process->getOutput());

        /*
         * Complete requests    10    All 10 requests reached the server.
         * Failed requests    9    Nine requests did not complete successfully.
         * Non-2xx responses    9    Nine requests received an HTTP status that is not 2xx (e.g., 409 Conflict or 400 Bad Request).
         */

        // Cleanup
        unlink($jsonFilePath);

        return Command::SUCCESS;
    }
}
