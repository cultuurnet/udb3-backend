<?php


namespace CultuurNet\UDB3\StringFilter;

class TidyStringFilter implements StringFilterInterface
{
    public function filter($string)
    {
        $config = array('show-body-only' => true);

        /** @var \tidy $tidy */
        $tidy = tidy_parse_string($string, $config, 'UTF8');
        $tidy->cleanRepair();

        return tidy_get_output($tidy);
    }
}
