<?php

namespace CultuurNet\UDB3\Offer\Mock\Commands;

use CultuurNet\UDB3\Offer\Commands\AbstractAddLabel;

/**
 * Used in the OfferCommandHandlerTest to verify that the command handler
 * ignores AddLabel commands from incorrect namespaces.
 */
class AddLabel extends AbstractAddLabel
{
}
