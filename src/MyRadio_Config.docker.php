<?php

const ENV_PREFIX = "MYRADIO_";

$cfgRC = new ReflectionClass('MyRadio\Config');

foreach ($_ENV as $var => $val) {
    if (strpos($var, ENV_PREFIX) === false) {
        continue;
    }
    $varName = strtolower(substr($var, strlen(ENV_PREFIX)));
    foreach ($cfgRC->getProperties(ReflectionProperty::IS_STATIC) as $prop) {
        if (strtolower($prop->getName()) === $varName) {
            // Not all properties have a type, so assume they're strings
            $type = $prop->getType();
            if ($type instanceof ReflectionNamedType && (!$type->isBuiltin() || $type->getName() !== 'string')) {
                $val = json_decode($val, true);
            }
            $cfgRC->setStaticPropertyValue($prop->getName(), $val);
        }
    }
}
