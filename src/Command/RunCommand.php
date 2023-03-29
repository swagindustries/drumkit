<?php

namespace SwagIndustries\MercureRouter\Command;

use SwagIndustries\MercureRouter\Configuration\ConfigFileValidator;
use SwagIndustries\MercureRouter\Configuration\OptionsFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'run')]
class RunCommand extends Command
{
    protected static $defaultDescription = 'Start Drumkit (run a Mercure server)';
    protected function configure(): void
    {
        $this
            // the command help shown when running the command with the "--help" option
            ->setHelp(<<<HELP
            This commmand run a mercure server.
            
            You can use a configuration file with the option `--config`.
            The file format accepted is json5 (this means a json file where you can comment in),
            you can find its content reference in the official documentation.
            HELP)
            ->addOption(
                'config',
                null,
                InputOption::VALUE_REQUIRED,
                'Path to your configuration file (optional)',
                null,
                function (CompletionInput $input): array {
                    $potentialConfigurationFiles = [
                        './configuration.json',
                        '/etc/drumkit/configuration.json',
                        '/etc/drumkit.json'
                    ];

                    return array_map('is_file', $potentialConfigurationFiles);
                }
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // TODO: manage config argument
        $configFile = $input->getOption('config');
        $config = null;
        if ($configFile !== null) {
            $validator = new ConfigFileValidator();
            $resolvedConfig = $validator->validate(json5_decode(
                file_get_contents($configFile),
                associative: true,
                options: \JSON_THROW_ON_ERROR
            ));

            $options = OptionsFactory::fromFile($resolvedConfig);
        }

        // TODO: actually run the server

        return Command::SUCCESS;
    }
}
