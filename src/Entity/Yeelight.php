<?php

namespace App\Entity;

use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class Yeelight
{
    const POWER_OFF = 'off';
    const POWER_ON = 'on';

    private string $name;

    private string $ip;

    private int $port = 55443;

    private $socket = null;

    private array $jobs = [];

    private static ?OutputInterface $output = null;

    private bool $needToCommit = false;

    public function setNeedToCommit(bool $needToCommit): self
    {
        $this->needToCommit = $needToCommit;

        return $this;
    }

    public static function setOutput(OutputInterface $output)
    {
        self::$output = $output;
    }

    public static function getOutput(): OutputInterface
    {
        if (null === self::$output) {
            return new NullOutput();
        }

        return self::$output;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function setIp(string $ip): self
    {
        $this->ip = $ip;
        return $this;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function setPort(int $port): self
    {
        $this->port = $port;

        return $this;
    }

    private function initSocket(): bool
    {
        try {
            $this->socket = fsockopen($this->ip, $this->port);
        } catch (\Exception $exception) {
            $this->info("Can't create connection");
        }
        if (!$this->socket) {
            return false;
        }

        stream_set_blocking($this->socket, false);

        return true;
    }

    public function setPower(string $state): self
    {
        $this->info("{$this->getName()}: set power to '$state'");
        $this->executeJob('set_power', $state);

        return $this;
    }

    public function setColor($hex): self
    {
        $this->info("{$this->getName()}: set color to '$hex'");
        $this->executeJob('set_rgb', $hex);

        return $this;
    }

    public function setBright(int $percentage): self
    {
        $percentage = $percentage < 1 ? 1 : ($percentage > 100 ? 100 : $percentage);
        $this->info("set bright to $percentage%");
        $this->executeJob('set_bright', $percentage);

        return $this;
    }

    public function setHue($hue): self
    {
        $hue %= 360;
        $this->info("set hue to '$hue'");
        $this->executeJob('set_hsv', $hue);

        return $this;
    }

    public function turnOn(): self
    {
        return $this->setPower(self::POWER_ON);
    }

    public function turnOff(): self
    {
        return $this->setPower(self::POWER_OFF);
    }

    public function togglePower(): self
    {
        self::$output->writeln("{$this->getName()}: toggle power");
        $this->executeJob('toggle');

        return $this;
    }

    public function setCurrentStateAsDefault(): self
    {
        self::$output->writeln("{$this->getName()}: set current state as default");
        $this->executeJob('set_default');

        return $this;
    }

    public function setTemperature(int $temperature, string $mode = 'sudden', int $duration = 30): self
    {
        $temperature = $temperature < 1700 ? 1700 : ($temperature > 6500 ? 6500 : $temperature);
        $this->info("set temperature to '$temperature'");
        $this->executeJob('set_ct_abx', $temperature, $mode, $duration);
        return $this;
    }

    public function getPowerState(): string
    {
        $output = $this->executeJob('get_prop', 'power');
        $output = json_decode($output);

        return isset($output->result) ? $output->result[0] : '';
    }

    public function getBrightPercentage(): int
    {
        $output = $this->executeJob('get_prop', 'bright');
        $output = json_decode($output);

        $brightPercentage = isset($output->result) ? $output->result[0] : -1;

        $this->info("current bright to $brightPercentage%");

        return $brightPercentage;
    }

    private function executeJob(string $methodName, ...$params): int|string
    {
        $job = ['id' => 0, 'method' => $methodName, 'params' => $params];

        if ($this->needToCommit) {
            return $this->addJob($job);
        }

        if (!$this->initSocket()) {
            return json_encode(['error' => "Can\'t connect to {$this->name}'s address"]);
        }

        fwrite($this->socket, json_encode($job) . "\r\n");
        fflush($this->socket);
        usleep(150000);
        $output = fgets($this->socket);
        fclose($this->socket);

        return $output;
    }

    public function commit(): string
    {
        if (!$this->needToCommit) {
            return '';
        }

        return $this->execute($this->jobs);
    }

    private function execute(array $jobs): string
    {
        if (empty($jobs)) {
            return '';
        }

        if (!$this->initSocket()) {
            return json_encode(['error' => "Can\'t connect to {$this->name}'s address"]);
        }

        $output = [];
        foreach ($jobs as $job) {
            fwrite($this->socket, json_encode($job) . "\r\n");
            fflush($this->socket);
            usleep(150000);
            $output[] = fgets($this->socket);
        }
        fclose($this->socket);

        return json_encode($output);
    }

    private function addJob(array $job = []): int
    {
        $job['id'] = count($this->jobs);
        $this->jobs[] = $job;

        return $job['id'];
    }

    private function info($string)
    {
        self::getOutput()->writeln("{$this->getName()}: $string");
    }
}
