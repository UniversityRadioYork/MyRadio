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

$typeConfigDecorator = function($typeConfig, $typeDefinitionNode) {
    $name = $typeConfig['name'];
    switch ($name) {
        case "Query":
            // Gets special handling
            $typeConfig['fields']['node']['resolve'] = function($_, $args) {
                $id_val = base64_decode($args['id']);
                list($type, $id) = explode('#', $id_val);
                $rc = new ReflectionClass($type);
                if (!($rc->isSubclassOf(\MyRadio\ServiceAPI\ServiceAPI::class))) {
                    throw new MyRadioException("Tried to resolve node $type#$id but it's not a ServiceAPI");
                }
                return $type::getInstance($id);
            };
            $typeConfig['fields']['node']['resolveType'] = function($value, $context, ResolveInfo $info) {
                // Go through all the defined types, until we find one that binds to $value::class
                $className = get_class($value);
                if ($className === false) {
                    throw new MyRadioException('Tried to resolve a node that isn\'t a class!');
                }
                $rc = new ReflectionClass($className);
                if(!($rc->isSubclassOf(\MyRadio\ServiceAPI\ServiceAPI::class))) {
                    throw new MyRadioException("Tried to resolve $className through Node, but it's not a ServiceAPI");
                }
                $typeName = $className::getGraphQLTypeName();
                return $info->schema->getType($typeName);
            };

            $typeConfig['resolveField'] = function($source, $args, $context, ResolveInfo $info) {
                $fieldName = $info->fieldName;
                // If we're on the Query type, we're entering the graph, so we'll want a static method.
                // Unlike elsewhere in the graph, we can assume everything on Query will have an @bind.
                $bindDirective = GraphQLUtils::getDirectiveByName($info, 'bind');
                if (!$bindDirective) {
                    throw new MyRadioException("Tried to resolve $fieldName on Query but it didn't have an @bind");
                }
                $bindArgs = GraphQLUtils::getDirectiveArguments($bindDirective);
                if (isset($bindArgs['class'])) {
                    // we know class is a string
                    /** @noinspection PhpPossiblePolymorphicInvocationInspection */
                    $className = $bindArgs['class']->value;
                } else {
                    throw new MyRadioException("Tried to resolve $fieldName on Query but its @bind didn't have a class");
                }
                if (isset($bindArgs['method'])) {
                    $methodName = $bindArgs['method']->value;
                } else {
                    throw new MyRadioException("Tried to resolve $fieldName on Query but its @bind didn't have a method");
                }
                // Wonderful!
                $clazz = new ReflectionClass($className);
                $meth = $clazz->getMethod($methodName);
                // TODO authz
                return GraphQLUtils::processScalarIfNecessary($info, GraphQLUtils::invokeNamed($meth, null, $args));
            };
            break;
        case "Mutation":
            throw new MyRadioException('Mutations not supported');
    }
    return $typeConfig;
};

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
    // Okay, we're in the Wild West. We're on an object and we need to get a field.
    // First, check if we're on an array
    if (is_array($source)) {
        if (isset($source[$fieldName])) {
            return GraphQLUtils::processScalarIfNecessary($info, $source[$fieldName]);
        }
    }
    // Next, check if it's an object
    if (is_object($source)) {
        // Before we move on, check if we're getting `id` on a `Node`. This merits special handling.
        if ($fieldName === "id") {
            /** @noinspection PhpParamsInspection - we know `Node` is an interface */
            if ($info->parentType->implementsInterface(
                $info->schema->getType("Node")
            )) {
                $clazz = get_class($source);
                // If we've used @bind on the ID, use that method. Otherwise, assume $source->getID() exists.
                // Also, ID methods can't take any arguments, by dint of the Node spec
                $id = null;
                if (isset($methodName) && method_exists($source, $methodName)) {
                    $id = $source->{$methodName}();
                } else if (method_exists($source, "getID")) {
                    $id = $source->getID();
                } else {
                    throw new MyRadioException("Couldn't resolve ID for type $clazz");
                }
                // Not done yet. Remember, GraphQL IDs have to be unique
                // We combine it with the class name and base64encode it
                return base64_encode($clazz . '#' . strval($id));
            }
        }
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
                // Assume we know what we're doing.
                get_class($source),
                $methodName
            );
            return GraphQLUtils::processScalarIfNecessary($info,
                GraphQLUtils::invokeNamed($meth, $source, $args)
            );
        }
        // Giving up on methods. Last shot: is it a property?
        if (isset($source->{$fieldName})) {
            return GraphQLUtils::processScalarIfNecessary($info, $source->{$fieldName});
        }
        // Darn.
        throw new MyRadioException("Couldn't track down a resolution for $fieldName");
    }

    // It's probably a scalar. Return it directly.
    return GraphQLUtils::processScalarIfNecessary($info, $source);
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
