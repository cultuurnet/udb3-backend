<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

use CultuurNet\UDB3\Model\Validation\Place\PlaceValidator;
use Respect\Validation\Exceptions\GroupedValidationException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class ValidatePlaceJsonLdCommand extends AbstractCommand
{
    private const URL = 'url';

    /**
     * @inheritdoc
     */
    public function configure()
    {
        $this
            ->setName('place:jsonld:validate')
            ->setDescription('Validate place JSON-LD')
            ->addArgument(
                self::URL,
                InputArgument::REQUIRED,
                'Full URL to the JSON-LD file to validate.'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $json = file_get_contents($input->getArgument(self::URL));
        $decoded = json_decode($json, true);

        $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
        $logger = new ConsoleLogger($output);

        try {
            $validator = new PlaceValidator();
            $validator->assert($decoded);
            $logger->info('Place JSON-LD is valid!');
        } catch (GroupedValidationException $e) {
            foreach ($e->getMessages() as $message) {
                $logger->error($message);

                return 1;
            }
        }

        return 0;
    }
}
