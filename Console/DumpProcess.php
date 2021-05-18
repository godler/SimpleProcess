<?php

namespace Klik\SimpleProcess\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Symfony\Component\Process\Process;
use Symfony\Component\Workflow\Dumper\GraphvizDumper;
use Symfony\Component\Workflow\Dumper\PlantUmlDumper;
use Symfony\Component\Workflow\Workflow as SynfonyWorkflow;

class DumpProcess extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'simple_process:dump';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $class = $this->argument('class');

        

        $this->info($class);

        if (!isset($class)) {
            throw new Exception("Workflow $class is not configured.");
        }

        $subject    = new $class;
        
        $definition = $subject->getDefinition();

        $dumper = new PlantUmlDumper('square'); //arrow | square

       // $dotCommand = "dot -Tpng -o $class.png";

        $file_name = str_replace('\\','_', $class).'.png';

        $dotCommand = "java -jar plantuml.jar -p  > $file_name";

        $process = Process::fromShellCommandline($dotCommand);
        $process->setInput($dumper->dump($definition));
        $process->mustRun();
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['class', InputArgument::REQUIRED, 'Provide class for process definition'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null],
        ];
    }
}
