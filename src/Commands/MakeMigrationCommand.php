<?php

namespace App\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MakeMigrationCommand extends Command {
  protected function configure() {
    $this
      ->setName('make:migration')
      ->setDescription('Generate Model Class')
      ->addArgument(
        'name',
        InputArgument::REQUIRED,
        'Model name to Generate'
      )
      ->addArgument(
        'table',
        InputArgument::OPTIONAL,
        'Table name'
      )
      ->addOption(
        'update',
        'u',
        InputOption::VALUE_OPTIONAL,
        'Update table',
        false
      )
    ;
  }
  protected function execute(InputInterface $input, OutputInterface $output) {
    $templateFile = resources_path() . '/migration_template.php';

    $name = $input->getArgument('name');
    $nameComponents = explode('_', $name);

    $table = $input->getArgument('table') ?? $nameComponents[1] ?? '';

    $update = $input->getOption('update') !== false;

    $class = str_replace('_', '', ucwords($name, '_'));
    $column = $nameComponents[count($nameComponents) - 1];

    ob_start();
    include $templateFile;

    $source = ob_get_contents();
    ob_end_clean();

    $directory = base_path() . '/migrations';
    $stamp = date('Y_m_d_His');
    $file = "{$directory}/{$stamp}_{$name}.php";

    if (!file_exists($file)) {
      $handle = fopen($file, "w");
      fwrite($handle, $source);
      fclose($handle);

      $output->writeln("Created $name in migrations");
    } else {
      $output->writeln("Class migration already Exists!");
    }
  }
}
