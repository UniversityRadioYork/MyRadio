<?php

use GraphQL\Error\FormattedError;
use GraphQL\GraphQL;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Utils\BuildSchema;
use MyRadio\MyRadio\GraphQLUtils;
use MyRadio\MyRadioException;

$debug = ($_GET['debug'] ?? 'false') === 'true';

$schemaText = file_get_contents("../../schema/api.graphql");

if ($schemaText === false) {
    throw new MyRadioException('Failed to get API schema!');
}


$schema = BuildSchema::build($schemaText);

if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true) ?: [];
} else {
    $data = $_REQUEST;
}

function graphQlResolver($source, $args, $context, ResolveInfo $info) {
    $fieldName = $info->fieldName;
    // First up, check if we have a bind directive
    $bindDirective = GraphQLUtils::getDirectiveByName($info, 'bind');
    if ($bindDirective) {
        $bindArgs = GraphQLUtils::getDirectiveArguments($bindDirective);
        // If it has a method set, use that. It'll override the rest of method resolution, even if it doesn't exist.
        if (isset($bindArgs['method'])) {
            // we know method is a string
            /** @noinspection PhpPossiblePolymorphicInvocationInspection */
            $methodName = $bindArgs['method']->value;
        }
    }

    // If we're on the Query type, we're entering the graph, so we'll want a static method.
    // Unlike elsewhere in the graph, we can assume everything on Query will have an @bind.
    if ($info->parentType->name === 'Query') {
        if (!$bindDirective) {
            throw new MyRadioException("Tried to resolve $fieldName on Query but it didn't have an @bind");
        }
        if (isset($bindArgs['class'])) {
            // we know class is a string
            /** @noinspection PhpPossiblePolymorphicInvocationInspection */
            $className = $bindArgs['class']->value;
        } else {
            throw new MyRadioException("Tried to resolve $fieldName on Query but its @bind didn't have a class");
        }
        if (!isset($methodName)) {
            throw new MyRadioException("Tried to resolve $fieldName on Query but its @bind didn't have a method");
        }

        // Wonderful!
        $clazz = new ReflectionClass($className);
        $meth = $clazz->getMethod($methodName);
        // TODO authz
        return GraphQLUtils::invokeNamed($meth, null, $args);
    }
    // TODO
    if ($info->parentType->name === 'Mutation') {
        throw new MyRadioException('Mutations not yet supported.');
    }

    // Okay, we're in the Wild West. We're on an object and we need to get a field.
    // First, check if we're on an array
    if (is_array($source)) {
        if (isset($source[$fieldName])) {
            return $source[$fieldName];
        }
    }
    // Next, check if it's an object
    if (is_object($source)) {
        // At this point, we check the method given by @bind again.
        if (isset($methodName) && method_exists($source, $methodName)) {
            // Yipee!
        } else {
            // Right, nothing there.
            // Check on the method directly
            if (method_exists($source, $fieldName)) {
                $methodName = $fieldName;
            } else {
                // Try making it into a getter
                $getterName = 'get' . strtoupper($fieldName[0]) . substr($fieldName, 1);
                if (method_exists($source, $getterName)) {
                    $methodName = $getterName;
                }
            }
        }
        // Okay. Have we tracked down a method?
        if (isset($methodName)) {
            // Yay. Call it!
            // (We'll get a ReflectionException here if it's inaccessible. But That's Okay.
            // TODO authz
            $meth = new ReflectionMethod(
                // Since it's a @bind, assume we know what we're doing.
                get_class($source),
                $methodName
            );
            return GraphQLUtils::invokeNamed($meth, $source, $args);
        }
        // Giving up on methods. Last shot: is it a property?
        if (isset($source->{$fieldName})) {
            return $source->{$fieldName};
        }
        // Darn.
        throw new MyRadioException("Couldn't track down a resolution for $fieldName");
    }

    // It's probably a scalar. Return it directly.
    return $source;
}

try {
    $queryResult = GraphQL::executeQuery(
        $schema,
        $data['query'],
        null,
        null,
        (array) $data['variables'],
        null,
        'graphQlResolver'
    );
    $result = $queryResult->toArray($debug);
} catch (Exception $e) {
    $status = 500;
    $result = [
        'errors' => FormattedError::createFromException($e, $debug)
    ];
}

header('Content-Type: application/json', true, $status);
echo json_encode($result);
