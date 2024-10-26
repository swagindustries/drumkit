<?php
/**
 * This file is a part of mercure-router-php package.
 *
 * (c) Swag Industries <nek.dev@gmail.com>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

declare(strict_types=1);

namespace SwagIndustries\MercureRouter\Command;

use SwagIndustries\MercureRouter\Configuration\ConfigFileValidator;
use SwagIndustries\MercureRouter\Configuration\Options;
use SwagIndustries\MercureRouter\Configuration\OptionsFactory;
use SwagIndustries\MercureRouter\Exception\MissingOptionException;
use SwagIndustries\MercureRouter\MercureServerFactory;
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

    public function __construct(
        private ConfigFileValidator $configValidator = new ConfigFileValidator(),
        private MercureServerFactory $serverFactory = new MercureServerFactory(),
    ) {
        parent::__construct('run');
    }

    protected function configure(): void
    {
        $this
            // the command help shown when running the command with the "--help" option
            ->setHelp(<<<HELP
            This commmand run a mercure server.

            You can use a configuration file with the option `--config`.
            The file format accepted is json5 (this means a json file where you can comment in),
            you can find its content reference in the official documentation.

            Usage example for development :
            bin/drumkit --tls-cert=ssl/mercure-router.local.pem --tls-key=ssl/mercure-router.local-key.pem --dev --active-subscriptions

            Usage example for production :
            bin/drumkit --config configuration.json
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
                OptionsFactory::OPTION_TLS_CERT,
                null,
                InputOption::VALUE_REQUIRED,
                'Path to an TLS certificate used for HTTPS support - overrides the file config'
            )
            ->addOption(
                OptionsFactory::OPTION_TLS_KEY,
                null,
                InputOption::VALUE_REQUIRED,
                'Path to an TLS key used for HTTPS support - overrides the file config'
            )
            ->addOption(
                OptionsFactory::OPTION_FEATURE_SUBSCRIPTIONS,
                null,
                InputOption::VALUE_NONE,
                'Enables the active subscriptions feature'
            )
            ->addOption(
                OptionsFactory::OPTION_SECURITY_PUBLISHER_KEY,
                'sec-pub-key',
                InputOption::VALUE_REQUIRED,
                'Private key for publisher JWT validation',
            )
            ->addOption(
                OptionsFactory::OPTION_SECURITY_PUBLISHER_ALG,
                'sec-pub-alg',
                InputOption::VALUE_REQUIRED,
                'Security algorithm to be use for JWT encryption for publishing. Defaults to "' . Options::DEFAULT_SECURITY_ALG->value . '"'
            )
            ->addOption(
                OptionsFactory::OPTION_SECURITY_SUBSCRIBER_KEY,
                'sec-sub-key',
                InputOption::VALUE_REQUIRED,
                'Private key for subscriber JWT validation',
            )
            ->addOption(
                OptionsFactory::OPTION_SECURITY_SUBSCRIBER_ALG,
                'sec-sub-alg',
                InputOption::VALUE_REQUIRED,
                'Security algorithm to be use for JWT encryption for subscribing. Defaults to "' . Options::DEFAULT_SECURITY_ALG->value . '"'
            )
            ->addOption(
                OptionsFactory::OPTION_DEV,
                null,
                InputOption::VALUE_NONE,
                'Run the server in dev mode (shows more explicit errors, logs & run with xdebug)'
            )
            ->addOption(
                OptionsFactory::OPTION_CORS_ORIGIN_KEY,
                null,
                InputOption::VALUE_IS_ARRAY|InputOption::VALUE_REQUIRED,
                'Specified authorised cors domain',
                []
            )
            ->addOption(
                OptionsFactory::OPTION_PORT_HTTP,
                null,
                InputOption::VALUE_REQUIRED,
                'Port number for HTTP',
                80
            )
            ->addOption(
                OptionsFactory::OPTION_PORT_HTTPS,
                null,
                InputOption::VALUE_REQUIRED,
                'Port number for HTTPS',
                443
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configFile = $input->getOption('config');
        $tlsKey = $input->getOption(OptionsFactory::OPTION_TLS_KEY);
        $tlsCert = $input->getOption(OptionsFactory::OPTION_TLS_CERT);
        $devMode = $input->getOption('dev');

        if ($configFile !== null) {
            $resolvedConfig = $this->configValidator->validate(json5_decode(
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
        } else {
            try {
                $options = OptionsFactory::fromCommandOptions(
                    $tlsCert,
                    $tlsKey,
                    $input->getOption(OptionsFactory::OPTION_CORS_ORIGIN_KEY),
                    $input->getOption(OptionsFactory::OPTION_SECURITY_SUBSCRIBER_KEY),
                    $input->getOption(OptionsFactory::OPTION_SECURITY_SUBSCRIBER_ALG),
                    $input->getOption(OptionsFactory::OPTION_SECURITY_PUBLISHER_KEY),
                    $input->getOption(OptionsFactory::OPTION_SECURITY_PUBLISHER_ALG),
                    $input->getOption(OptionsFactory::OPTION_FEATURE_SUBSCRIPTIONS),
                    devMode: $devMode,
                    httpPort: (int) $input->getOption(OptionsFactory::OPTION_PORT_HTTP),
                    httpsPort: (int) $input->getOption(OptionsFactory::OPTION_PORT_HTTPS),
                );
            } catch (MissingOptionException $e) {
                $output->writeln('<error>'.$e->getMessage().'.</error>');
                $output->writeln('<info>Run command `drumkit --help` to learn more.</info>');
                return Command::FAILURE;
            }
        }

        $server = $this->serverFactory->create($options);
        $server->start();

        return Command::SUCCESS;
    }
}
