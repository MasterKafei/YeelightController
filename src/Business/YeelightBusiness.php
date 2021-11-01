<?php

namespace App\Business;

use App\Entity\Yeelight;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class YeelightBusiness
{
    private ParameterBagInterface $parameterBag;

    private array $yeelights = [];

    private array $scenarios = [];

    /** @required */
    public function setParameterBag(ParameterBagInterface $parameterBag): self
    {
        $this->parameterBag = $parameterBag;

        return $this;
    }

    public function getYeelights(): array
    {
        if (!empty($this->yeelights)) {
            return $this->yeelights;
        }

        $yeelights = [];
        foreach ($this->parameterBag->get('yeelights') as $yeelight) {
            $entity = new Yeelight();
            $entity
                ->setName($yeelight['name'])
                ->setIp($yeelight['ip'])
                ->setPort($yeelight['port'] ?? $entity->getPort());
            $yeelights[$entity->getName()] = $entity;
        }

        $this->yeelights = $yeelights;

        return $yeelights;
    }

    public function getYeelight(string $name): ?Yeelight
    {
        return $this->getYeelights()[$name] ?? null;
    }

    public function executeScenario(string $name): self
    {
        $scenario = $this->getScenario($name);

        foreach ($scenario['instructions'] ?? [] as $instruction) {
            $lights = $instruction['lights'] ?? [];
            if (!is_iterable($lights) && $lights !== 'all') {
                $lights = [$lights];
            }

            if ('all' !== $lights) {
                $yeelights = array_map(function (string $name) {
                    return $this->getYeelight($name);
                }, $lights);
            } else {
                $yeelights = $this->getYeelights();
            }

            foreach ($yeelights as $yeelight) {
                if (null === $yeelight) {
                    continue;
                }
                $yeelight->setNeedToCommit(true);
                foreach ($instruction as $key => $value) {
                    $this->executeInstruction($instruction, $key, $yeelight);
                }
                $yeelight->commit();
            }
            sleep($instruction['continueAfter'] ?? 0);
        }

        return $this;
    }

    public function executeInstruction(array $instruction, string $name, Yeelight $yeelight)
    {
        if (!isset($instruction[$name])) {
            return;
        }
        $value = $instruction[$name];

        switch ($name) {
            case('power'):
                if ($value === 'toggle') {
                    $yeelight->togglePower();
                } else {
                    $yeelight->setPower($value);
                }
                break;
            case('color'):
                $yeelight->setColor($value);
                break;
            case('hue'):
                $yeelight->setHue($value);
                break;
            case('bright'):
                $yeelight->setBright($value);
                break;
            case('temperature'):
                if (!is_iterable($value)) {
                    $yeelight->setTemperature($value);
                } else {
                    $yeelight->setTemperature($value[0], $value[1] ?? 'sudden', $value[2] ?? 30);
                }
                break;
            default:
                break;
        }
    }

    private function getScenario(string $name): array
    {
        return $this->getScenarios()[$name] ?? [];
    }

    private function getScenarios(): array
    {
        if (!empty($this->scenarios)) {
            return $this->scenarios;
        }

        foreach ($this->parameterBag->get('scenarios') as $scenario) {
            $this->scenarios[$scenario['name']] = $scenario;
        }
        return $this->scenarios;
    }

    public function getScenariosNames(): array
    {
        return array_keys($this->getScenarios());
    }
}
