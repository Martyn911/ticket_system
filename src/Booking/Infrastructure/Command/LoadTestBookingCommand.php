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

        // 1. Створення тимчасового JSON-файлу
        $jsonFilePath = sys_get_temp_dir().'/ab_post_data.json';
        file_put_contents($jsonFilePath, '{"clientId": "'.$testEvent->getId().'", "clientEmail": "test@example.com"}');

        // 2. Формування команди ab
        // Використовуємо імена сервісів Docker (nginx) та внутрішній порт (80)
        $url = self::HOST.'/api/events/'.$testEvent->getId().'/book';

        // Команда: 10 запитів (-n) з 10 одночасними клієнтами (-c)
        $command = [
            'ab',
            '-n', '10',
            '-c', '10',
            '-p', $jsonFilePath,
            '-T', 'application/json',
            $url,
        ];

        // 3. Виконання команди за допомогою Symfony Process
        $process = new Process($command);
        $process->setTimeout(60); // Даємо 60 секунд на виконання

        $output->writeln('Executing: '.$process->getCommandLine());
        $process->run();

        // 4. Обробка результату
        if (!$process->isSuccessful()) {
            $io->error("ab command failed. Is 'ab' installed in the php-fpm container? (See Dockerfile)");
            $io->text($process->getErrorOutput());

            return Command::FAILURE;
        }

        // 5. Виведення результату
        $io->success('ab test finished successfully. Analyzing results...');
        $output->writeln($process->getOutput());

        /*
         * Complete requests    10    Усі 10 запитів дійшли до сервера.
         * Failed requests    9    Дев'ять запитів не завершилися успіхом.
         * Non-2xx responses    9    Дев'ять запитів отримали код HTTP, який не є 2xx (наприклад, 409 Conflict або 400 Bad Request).
         */

        // Очищення
        unlink($jsonFilePath);

        return Command::SUCCESS;
    }
}
