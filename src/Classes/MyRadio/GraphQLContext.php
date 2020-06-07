<?php


namespace MyRadio\MyRadio;


class GraphQLContext
{
    private $warnings = [];

    public function addWarning(string $warning) {
        $this->warnings[] = $warning;
    }

    public function getWarnings() {
        return $this->warnings;
    }
}