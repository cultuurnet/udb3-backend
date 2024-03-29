<?php

declare(strict_types=1);

use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\RDF\RdfNamespaces;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../vendor/autoload.php';

JsonSchemaLocator::setSchemaDirectory(__DIR__ . '/../vendor/publiq/udb3-json-schemas');
RdfNamespaces::register();

class_alias(TestCase::class, 'PHPUnit_Framework_TestCase');
