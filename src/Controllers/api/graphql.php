<?php

use GraphQL\Error\FormattedError;
use GraphQL\GraphQL;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Utils\BuildSchema;
use MyRadio\MyRadio\GraphQLContext;
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
        case "Node":
            $typeConfig['resolveType'] = function($value, $context, ResolveInfo $info) {
                // If it's a Node, it'll implement ServiceAPI, and thus we can use getGraphQLTypeName
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
            break;
        case "Query":
            // Gets special handling
            $typeConfig['fields'] = function() use ($typeConfig) {
                $orig = is_callable($typeConfig['fields']) ? $typeConfig['fields']() : $typeConfig['fields'];
                return array_merge_recursive(
                    $orig,
                    [
                        'node' => [
                            'resolve' => function($value, $args, GraphQLContext $context, ResolveInfo $info) {
                                $id_val = base64_decode($args['id']);
                                list($type, $id) = explode('#', $id_val);
                                if ($type[0] !== '\\') {
                                    $type = '\\' . $type;
                                }
                                $rc = new ReflectionClass($type);
                                if (!($rc->isSubclassOf(\MyRadio\ServiceAPI\ServiceAPI::class))) {
                                    throw new MyRadioException("Tried to resolve node $type#$id but it's not a ServiceAPI");
                                }
                                // Node resolution checks authorisation for $type::toDataSource
                                if (GraphQLUtils::isAuthorisedToAccess($info, $type, 'toDataSource')) {
                                    return $type::getInstance($id);
                                } else {
                                    return GraphQLUtils::returnNullOrThrowForbiddenException($info);
                                }
                            }
                        ]
                    ]
                );
            };

            $typeConfig['resolveField'] = function($source, $args, GraphQLContext $context, ResolveInfo $info) {
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
                if (GraphQLUtils::isAuthorisedToAccess($info, $className, $methodName)) {
                    return GraphQLUtils::processScalarIfNecessary(
                        $info,
                        GraphQLUtils::invokeNamed($meth, null, $args
                        )
                    );
                } else {
                    return GraphQLUtils::returnNullOrThrowForbiddenException($info);
                }
            };
            break;
        case "Mutation":
            throw new MyRadioException('Mutations not supported');
    }
    return $typeConfig;
};

$schema = BuildSchema::build($schemaText, $typeConfigDecorator);

if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true) ?: [];
} else {
    $data = $_REQUEST;
}

function graphQlResolver($source, $args, GraphQLContext $context, ResolveInfo $info) {
    $typeName = $info->parentType->name;
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
            if (GraphQLUtils::isAuthorisedToAccess($info, null, null)) {
                return GraphQLUtils::processScalarIfNecessary($info, $source[$fieldName]);
            } else {
                $context->addWarning("Unauthorised to access $typeName::$fieldName");
                return GraphQLUtils::returnNullOrThrowForbiddenException($info);
            }
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
                // Also note that `id` is bypassed from authorization, as it's controlled by access to the parent object
                return base64_encode($clazz . '#' . strval($id));
            }
        }
        // Now, check if it's a meta field, as given by the @meta directive
        $metaDirective = GraphQLUtils::getDirectiveByName($info, "meta");
        if ($metaDirective !== null) {
            $metaArgs = GraphQLUtils::getDirectiveArguments($metaDirective);
            // Authorization for metadata is the same as toDataSource
            // TODO is this really the best way
            if (GraphQLUtils::isAuthorisedToAccess($info, get_class($source), "toDataSource")) {
                // This Is Fine.
                /** @noinspection PhpPossiblePolymorphicInvocationInspection */
                /** @noinspection PhpUndefinedMethodInspection */
                return $source->getMeta($metaArgs['key']->value);
            } else {
                $context->addWarning("Unauthorised to access $typeName::$fieldName");
                return GraphQLUtils::returnNullOrThrowForbiddenException($info);
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
            // Great. Can we access it?
            if (GraphQLUtils::isAuthorisedToAccess($info, get_class($source), $methodName)) {
                // Yay. Call it!
                // (We'll get a ReflectionException here if it's inaccessible. But That's Okay.
                $meth = new ReflectionMethod(
                // Assume we know what we're doing.
                    get_class($source),
                    $methodName
                );
                return GraphQLUtils::processScalarIfNecessary($info,
                    GraphQLUtils::invokeNamed($meth, $source, $args)
                );
            } else {
                // So the method exists, but we can't access it.
                $context->addWarning("Unauthorised to access $typeName::$fieldName");
                return GraphQLUtils::returnNullOrThrowForbiddenException($info);
            }
        }
        // Giving up on methods. Last shot: is it a property?
        if (isset($source->{$fieldName})) {
            // Right. Can we access it?
            if (GraphQLUtils::isAuthorisedToAccess($info, get_class($source), $fieldName)) {
                return GraphQLUtils::processScalarIfNecessary($info, $source->{$fieldName});
            } else {
                $context->addWarning("Unauthorised to access $typeName::$fieldName");
                return GraphQLUtils::returnNullOrThrowForbiddenException($info);
            }
        }
        // Darn.
        throw new MyRadioException("Couldn't track down a resolution for $fieldName");
    }

    // It's probably a scalar. Return it directly.
    // Check authz just in case it's overridden
    if (GraphQLUtils::isAuthorisedToAccess($info, null, null)) {
        return GraphQLUtils::processScalarIfNecessary($info, $source);
    } else {
        $context->addWarning("Unauthorised to access $typeName::$fieldName");
        return GraphQLUtils::returnNullOrThrowForbiddenException($info);
    }
}

$ctx = new GraphQLContext();

try {
    $queryResult = GraphQL::executeQuery(
        $schema,
        $data['query'],
        null,
        $ctx,
        (array) $data['variables'],
        null,
        'graphQlResolver'
    );
    $result = $queryResult->toArray($debug);
    $warnings = $ctx->getWarnings();
    if (count($warnings) > 0) {
        $result['warnings'] = $warnings;
    }
} catch (Exception $e) {
    $status = $e instanceof MyRadioException ? $e->getCode() : 500;
    $result = [
        'errors' => FormattedError::createFromException($e, $debug)
    ];
}

header('Content-Type: application/json', true, $status);
echo json_encode($result);
