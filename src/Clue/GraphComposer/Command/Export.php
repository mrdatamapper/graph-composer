<?php

namespace Clue\GraphComposer\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Clue\GraphComposer\Graph\GraphComposer;

class Export extends Command
{
    protected function configure()
    {
        $this->setName('export')
            ->setDescription('Export dependency graph image for given project directory')
            ->addArgument('output', InputArgument::REQUIRED, 'Path to output image file')
            ->addArgument('dir', InputArgument::IS_ARRAY, 'Path to project directory to scan', ['.'])
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Image format (svg, png, jpeg)', 'svg')
            ->addOption('filter', null, InputOption::VALUE_OPTIONAL, 'Filter to apply on dependency name')
            ->addOption('dependency-version', 'd', InputOption::VALUE_OPTIONAL, 'Show dependency version', 1)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $graph = new GraphComposer($input->getArgument('dir'));
        $graph->setFilter($input->getOption('filter'));
        $graph->setShowVersion($input->getOption('dependency-version'));

        $target = $input->getArgument('output');
        if ($target !== 'print') {
            if (is_dir($target)) {
                $target = rtrim($target, '/').'/graph-composer.svg';
            }

            $filename = basename($target);
            $pos = strrpos($filename, '.');
            if ($pos !== false && isset($filename[$pos + 1])) {
                // extension found and not empty
                $graph->setFormat(substr($filename, $pos + 1));
            }
        }

        $format = $input->getOption('format');
        if ($format !== null) {
            $graph->setFormat($format);
        }

        $path = $graph->getImagePath();

        if ($target !== 'print') {
            rename($path, $target);
        } else {
            readfile($path);
        }
    }
}
