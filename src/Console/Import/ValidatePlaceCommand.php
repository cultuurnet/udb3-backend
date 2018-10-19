<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex\Console\Import;

use CultuurNet\UDB3\Model\Place\Place;
use CultuurNet\UDB3\Model\Serializer\Place\PlaceDenormalizer;
use function file_get_contents;
use function json_decode;
use Knp\Command\Command;
use Respect\Validation\Exceptions\AllOfException;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Exceptions\ValidationException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ValidatePlaceCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('import:validate-place')
            ->addArgument(
            'url',
            InputArgument::REQUIRED,
            'url of the place JSON-LD to validate'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $denormalizer = new PlaceDenormalizer();

        $placeJSONLD = file_get_contents($input->getArgument('url'));
        $placeJSONLD = json_decode($placeJSONLD, TRUE);

        try {
            $place = $denormalizer->denormalize($placeJSONLD, Place::class);
        }
        catch (NestedValidationException $e) {
            foreach ($e->getMessages() as $message) {
                $output->writeln($message);
            }
        }
    }
}