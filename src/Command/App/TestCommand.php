<?php

namespace Sourceml\Command\App;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends Command {

    protected function configure() {
        $this
            ->setName('sourceml:app:test')
            ->setDescription('test un truc')
/*
            ->addArgument(
                'name',
                InputArgument::OPTIONAL,
                'Who do you want to greet?'
            )
            ->addOption(
                'yell',
                null,
                InputOption::VALUE_NONE,
                'If set, the task will yell in uppercase letters'
            )
*/
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
    }

}
