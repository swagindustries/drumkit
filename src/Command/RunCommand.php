<?php

namespace SwagIndustries\MercureRouter\Command;

use SwagIndustries\MercureRouter\Configuration\ConfigFileValidator;
use SwagIndustries\MercureRouter\Configuration\OptionsFactory;
use SwagIndustries\MercureRouter\Server;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'run')]
class RunCommand extends Command
{
    private const OPTION_TLS_KEY = 'tls-key';
    private const OPTION_TLS_CERT = 'tls-cert';
    private const OPTION_FEATURE_SUBSCRIPTIONS = 'active-subscriptions';
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
            ->addOption(
                self::OPTION_TLS_CERT,
                null,
                InputOption::VALUE_OPTIONAL,
                'Path to an TLS certificate used for HTTPS support - overrides the file config'
            )
            ->addOption(
                self::OPTION_TLS_KEY,
                null,
                InputOption::VALUE_OPTIONAL,
                'Path to an TLS key used for HTTPS support - overrides the file config'
            )
            ->addOption(
                self::OPTION_FEATURE_SUBSCRIPTIONS,
                null,
                InputOption::VALUE_NONE,
                'Enables the active subscriptions feature'
            )
            ->addOption(
                'dev',
                null,
                InputOption::VALUE_NONE,
                'Run the server in dev mode (shows more explicit errors, logs & run with xdebug)'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configFile = $input->getOption('config');
        $tlsKey = $input->getOption(self::OPTION_TLS_KEY);
        $tlsCert = $input->getOption(self::OPTION_TLS_CERT);
        $devMode = $input->getOption('dev');

        if ($configFile !== null) {
            $validator = new ConfigFileValidator();
            $resolvedConfig = $validator->validate(json5_decode(
                file_get_contents($configFile),
                associative: true,
                options: \JSON_THROW_ON_ERROR
            ));

            $resolvedConfig['network']['tls_key_file'] = $tlsKey ?? $resolvedConfig['network']['tls_key_file'];
            $resolvedConfig['network']['tls_certificate_file'] = $tlsCert ?? $resolvedConfig['network']['tls_certificate_file'];

            $options = OptionsFactory::fromFile(
                config: $resolvedConfig,
                devMode: $devMode
            );
        } else if (!empty($tlsCert) && !empty($tlsKey)) {
            $options = OptionsFactory::fromCommandOptions(
                $tlsCert,
                $tlsKey,
                $input->getOption(self::OPTION_FEATURE_SUBSCRIPTIONS),
                $devMode
            );
        } else {
            $output->writeln('<error>You need to provide at least TLS certificates to run the server</error>');
            return Command::FAILURE;
        }

        $server = new Server($options);
        $server->start();

        return Command::SUCCESS;
    }
}
