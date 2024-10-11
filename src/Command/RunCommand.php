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
    private const OPTION_TLS_KEY = 'tls-key';
    private const OPTION_TLS_CERT = 'tls-cert';
    private const OPTION_FEATURE_SUBSCRIPTIONS = 'active-subscriptions';
    private const OPTION_SECURITY_PUBLISHER_ALG = 'security-publisher-algorithm';
    private const OPTION_SECURITY_PUBLISHER_KEY = 'security-publisher-key';
    private const OPTION_SECURITY_SUBSCRIBER_ALG = 'security-subscriber-algorithm';
    private const OPTION_SECURITY_SUBSCRIBER_KEY = 'security-subscriber-key';
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
                self::OPTION_TLS_CERT,
                null,
                InputOption::VALUE_REQUIRED,
                'Path to an TLS certificate used for HTTPS support - overrides the file config'
            )
            ->addOption(
                self::OPTION_TLS_KEY,
                null,
                InputOption::VALUE_REQUIRED,
                'Path to an TLS key used for HTTPS support - overrides the file config'
            )
            ->addOption(
                self::OPTION_FEATURE_SUBSCRIPTIONS,
                null,
                InputOption::VALUE_NONE,
                'Enables the active subscriptions feature'
            )
            ->addOption(
                self::OPTION_SECURITY_PUBLISHER_KEY,
                'sec-pub-key',
                InputOption::VALUE_REQUIRED,
                'Private key for publisher JWT validation',
            )
            ->addOption(
                self::OPTION_SECURITY_PUBLISHER_ALG,
                'sec-pub-alg',
                InputOption::VALUE_REQUIRED,
                'Security algorithm to be use for JWT encryption for publishing. Defaults to "' . Options::DEFAULT_SECURITY_ALG->value . '"'
            )
            ->addOption(
                self::OPTION_SECURITY_SUBSCRIBER_KEY,
                'sec-sub-key',
                InputOption::VALUE_REQUIRED,
                'Private key for subscriber JWT validation',
            )
            ->addOption(
                self::OPTION_SECURITY_SUBSCRIBER_ALG,
                'sec-sub-alg',
                InputOption::VALUE_REQUIRED,
                'Security algorithm to be use for JWT encryption for subscribing. Defaults to "' . Options::DEFAULT_SECURITY_ALG->value . '"'
            )
            ->addOption(
                'dev',
                null,
                InputOption::VALUE_NONE,
                'Run the server in dev mode (shows more explicit errors, logs & run with xdebug)'
            )
            ->addOption(
                'corsOrigin',
                null,
                InputOption::VALUE_IS_ARRAY|InputOption::VALUE_REQUIRED,
                'Specified authorised cors domain',
                []
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
        } else if (!empty($tlsCert) && !empty($tlsKey)) {
            $options = OptionsFactory::fromCommandOptions(
                $tlsCert,
                $tlsKey,
                $input->getOption('corsOrigin'),
                $input->getOption(self::OPTION_SECURITY_SUBSCRIBER_KEY),
                $input->getOption(self::OPTION_SECURITY_SUBSCRIBER_ALG),
                $input->getOption(self::OPTION_SECURITY_PUBLISHER_KEY),
                $input->getOption(self::OPTION_SECURITY_PUBLISHER_ALG),
                $input->getOption(self::OPTION_FEATURE_SUBSCRIPTIONS),
                devMode: $devMode
            );
        } else {
            $output->writeln('<error>You need to provide at least TLS certificates to run the server. Run command `drumkit --help` to learn more.</error>');
            return Command::FAILURE;
        }

        $server = $this->serverFactory->create($options);
        $server->start();

        return Command::SUCCESS;
    }
}
