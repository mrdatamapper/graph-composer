<?php

namespace Clue\GraphComposer\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Clue\GraphComposer\Graph\GraphComposer;

class Show extends Command
{
    protected function configure()
    {
        $this->setName('show')
            ->setDescription('Show dependency graph image for given project directory')
            ->addArgument('dir', InputArgument::IS_ARRAY, 'Path to project directory to scan (separate dir name and its color by ":" - exemple: ".:#000000")', ['.'])
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Image format (svg, png, jpeg)', 'svg')
            ->addOption('filter', null, InputOption::VALUE_OPTIONAL, 'Filter to apply on dependency name')
            ->addOption('dependency-version', 'd', InputOption::VALUE_OPTIONAL, 'Show dependency version', 1)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $graph = new GraphComposer($input->getArgument('dir'));
        $graph->setFormat($input->getOption('format'));
        $graph->setFilter($input->getOption('filter'));
        $graph->setShowVersion($input->getOption('dependency-version'));

        $graph->displayGraph();
    }
}
