<?php

namespace Bow\Console\Command;

use Bow\Console\GeneratorCommand;

class SeederCommand extends AbstractCommand
{
    /**
     * Create a seeder
     *
     * @param string $name
     */
    public function generate($seeder)
    {
        $generator = new GeneratorCommand(
            $this->setting->getSeederDirectory(),
            "{$seeder}_seeder"
        );

        if ($generator->fileExists()) {
            echo "\033[0;31mThe seeder already exists.\033[00m";

            exit(1);
        }

        $num = (int)  $this->arg->options()->get('--seed', 5);

        $generator->write('seed', [
            'num' => $num,
            'name' => $seeder
        ]);

        echo "\033[0;32mThe seeder has been created.\033[00m\n";

        exit(0);
    }

    /**
     * Make Seeder
     *
     * @return void
     */
    public function make()
    {
        $action = $this->arg->getParameter('action');

        if (!in_array($action, ['table', 'all'])) {
            $this->throwFailsAction('This action is not exists', 'help seed');

            $this->throwFailsCommand('help seed');
        }

        if ($action == 'all') {
            if ($this->arg->getParameter('target') != null) {
                $this->throwFailsAction('Bad command', 'help seed');
            }
        }

        $seeds_filenames = [];

        if ($action == 'all') {
            $seeds_filenames = glob($this->setting->getSeederDirectory().'/*_seeder.php');
        } elseif ($action == 'table') {
            $table_name = trim($this->arg->getParameter('target', null));

            if (is_null($table_name)) {
                echo Color::red('Specify the seeder table name');

                $this->throwFailsCommand('help seed');
            }

            if (!file_exists($this->setting->getSeederDirectory()."/{$table_name}_seeder.php")) {
                echo Color::red("Seeder $table_name not exists.");

                exit(1);
            }

            $seeds_filenames = [
                $this->setting->getSeederDirectory()."/{$table_name}_seeder.php"
            ];
        }

        $seed_collection = [];

        $faker = \Faker\Factory::create();

        foreach ($seeds_filenames as $filename) {
            $seeds = include $filename;
            $seed_collection = array_merge($seeds, $seed_collection);
        }

        try {
            foreach ($seed_collection as $table => $seeds) {
                $n = Database::table($table)->insert($seeds);

                echo Color::red("$n seed".($n > 1 ? 's' : '')." on $table table\n");
            }
        } catch (\Exception $e) {
            echo Color::red($e->getMessage());

            exit(1);
        }

        exit(0);
    }
}