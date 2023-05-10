<?php

namespace Commands;

use PHPUnit\Framework\TestCase;
use Grimarina\CityBike\Repositories\TripsRepository;
use Grimarina\CityBike\Commands\ImportTripsCommand;
use League\Csv\Reader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ImportTripsCommandTest extends TestCase
{
    private $tripsRepository;
    private $command;

    protected function setUp(): void
    {
        $this->tripsRepository = $this->createMock(TripsRepository::class);
        $this->command = new ImportTripsCommand($this->tripsRepository);
    }

    public function testItReturnsFailureIfTheFileDoesNotExist()
    {
        define('TRIPS_DIR', dirname(dirname(__DIR__)) . '/data/trips/');

        $input = new ArrayInput([
            'file' => 'nonexistentfile.csv',
        ]);
        $output = new BufferedOutput();

        $result = $this->command->run($input, $output);

        $this->assertEquals(Command::FAILURE, $result);
        $this->assertStringContainsString('File not found', $output->fetch());
    }

    public function testItReturnsFailureIfTheFileHasInvalidExtension()
    {
        $input = new ArrayInput([
            'file' => 'stations.txt',
        ]);
        $output = new BufferedOutput();

        $result = $this->command->run($input, $output);

        $this->assertEquals(Command::FAILURE, $result);
        $this->assertStringContainsString('Invalid file extension', $output->fetch());
    }

    public function testItReturnsSuccessIfFileIsValid()
    {
        $this->tripsRepository->expects($this->once())
            ->method('importCsv')
            ->with($this->isInstanceOf(Reader::class))
            ->willReturn(10);

        $input = new ArrayInput([
            'file' => '2021-05.csv',
        ]);
        $output = new BufferedOutput();

        $result = $this->command->run($input, $output);

        $this->assertEquals(Command::SUCCESS, $result);
        $this->assertStringContainsString('Imported 10 trips in 0 sec', $output->fetch());
    }
}
