<?php

namespace App\Command;

use App\Business\YeelightBusiness;
use App\Entity\Yeelight;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class StartCommand extends Command
{
    protected static $defaultName = 'app:start';

    private YeelightBusiness $yeelightBusiness;

    public function configure()
    {
        $this->addArgument('scenario', InputArgument::OPTIONAL);
    }

    /** @required */
    public function setYeelightBusiness(YeelightBusiness $yeelightBusiness): self
    {
        $this->yeelightBusiness = $yeelightBusiness;

        return $this;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        Yeelight::setOutput($output);
        $scenario = $input->getArgument('scenario');
        if (null === $scenario) {
            $questionHelper = $this->getHelper('question');
            $question = new ChoiceQuestion("Which scenario do you want to proceed ?\n", $this->yeelightBusiness->getScenariosNames());
            $scenario = $questionHelper->ask($input, $output, $question);
        }
        $this->yeelightBusiness->executeScenario($scenario);
        return Command::SUCCESS;
    }
}
