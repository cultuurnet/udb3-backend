<?php

declare(strict_types=1);

use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../vendor/autoload.php';

JsonSchemaLocator::setSchemaDirectory(__DIR__ . '/../vendor/publiq/stoplight-docs-uitdatabank/models');

class_alias(TestCase::class, 'PHPUnit_Framework_TestCase');
