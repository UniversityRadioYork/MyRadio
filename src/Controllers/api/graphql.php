<?php

use GraphQL\Error\FormattedError;
use GraphQL\GraphQL;
use GraphQL\Language\AST\TypeDefinitionNode;
use GraphQL\Language\AST\UnionTypeDefinitionNode;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\UnionType;
use GraphQL\Type\Definition\WrappingType;
use GraphQL\Utils\BuildSchema;
use MyRadio\MyRadio\GraphQLContext;
use MyRadio\MyRadio\GraphQLUtils;
use MyRadio\MyRadioException;
use MyRadio\ServiceAPI\MyRadio_User;
use MyRadio\ServiceAPI\ServiceAPI;

$debug = ($_GET['debug'] ?? 'false') === 'true';

$schemaText = file_get_contents("../../schema/api.graphql");

if ($schemaText === false) {
    throw new MyRadioException('Failed to get API schema!');
}

$typeConfigDecorator = function ($typeConfig, TypeDefinitionNode $typeDefinitionNode) {
    if ($typeDefinitionNode instanceof UnionTypeDefinitionNode) {
        $typeConfig['resolveType'] = function ($value, $context, ResolveInfo $info) {
            // If it's a Node, it'll implement ServiceAPI, and thus we can use getGraphQLTypeName
            if ($value instanceof ServiceAPI) {
                $typeName = $value::getGraphQLTypeName();
                return $info->schema->getType($typeName);
            }
            // If not, we need to get a bit crafty - in this case it might be an array.
            // Go through all the possible types of the union, exclude all the ones that are MyRadioObjects
            // (as they would be ServiceAPIs, and thus caught above)
            // If only one is left, use that, otherwise it's ambiguous
            /** @var UnionType $union */
            $union = $info->returnType;
            if ($union instanceof WrappingType) {
                $union = $union->getWrappedType(true);
            }
            /** @var InterfaceType $myRadioObjectType */
            $myRadioObjectType = $info->schema->getType('MyRadioObject');
            $candidates = [];
            foreach ($union->getTypes() as $test) {
                if (!($test->implementsInterface($myRadioObjectType))) {
                    $candidates[] = $test->name;
                }
            }
            if (count($candidates) === 1) {
                return $candidates[0];
            } else {
                $typeName = $info->returnType->name;
                $parent = $info->parentType->name;
                $field = $info->fieldName;
                throw new MyRadioException("Ambiguous union type $typeName for $parent.$field - candidates " . implode(', ', $candidates));
            }
        };
    }
    $name = $typeConfig['name'];
    switch ($name) {
        case "Node":
            $typeConfig['resolveType'] = function ($value, $context, ResolveInfo $info) {
                // If it's a Node, it'll implement ServiceAPI, and thus we can use getGraphQLTypeName
                $className = get_class($value);
                if ($className === false) {
                    throw new MyRadioException('Tried to resolve a node that isn\'t a class!');
                }
                $rc = new ReflectionClass($className);
                if (!($rc->isSubclassOf(ServiceAPI::class))) {
                    throw new MyRadioException("Tried to resolve $className through Node, but it's not a ServiceAPI");
                }
                $typeName = $className::getGraphQLTypeName();
                return $info->schema->getType($typeName);
            };
            break;
        case "Query":
            // Gets special handling
            $typeConfig['fields'] = function () use ($typeConfig) {
                $orig = is_callable($typeConfig['fields']) ? $typeConfig['fields']() : $typeConfig['fields'];
                return array_merge_recursive(
                    $orig,
                    [
                        'node' => [
                            'resolve' => function ($value, $args, GraphQLContext $context, ResolveInfo $info) {
                                $id_val = base64_decode($args['id']);
                                list($type, $id) = explode('#', $id_val);
                                if ($type[0] !== '\\') {
                                    $type = '\\' . $type;
                                }
                                $rc = new ReflectionClass($type);
                                if (!($rc->isSubclassOf(ServiceAPI::class))) {
                                    throw new MyRadioException(
                                        "Tried to resolve node $type#$id but it's not a ServiceAPI"
                                    );
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

            $typeConfig['resolveField'] = function ($source, $args, GraphQLContext $context, ResolveInfo $info) {
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
                    throw new MyRadioException(
                        "Tried to resolve $fieldName on Query but its @bind didn't have a class"
                    );
                }
                if (isset($bindArgs['method'])) {
                    $methodName = $bindArgs['method']->value;
                } else {
                    throw new MyRadioException(
                        "Tried to resolve $fieldName on Query but its @bind didn't have a method"
                    );
                }
                // Wonderful!
                $clazz = new ReflectionClass($className);
                $meth = $clazz->getMethod($methodName);
                if (GraphQLUtils::isAuthorisedToAccess($info, $className, $methodName)) {
                    return GraphQLUtils::processScalarIfNecessary(
                        $info,
                        GraphQLUtils::invokeNamed($meth, null, $args)
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

function graphQlResolver($source, $args, GraphQLContext $context, ResolveInfo $info)
{
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
    // Before we start, check if the bind directive has a class - in that case, it's a static method
    if (isset($bindArgs['class']) && isset($bindArgs['method'])) {
        // It's a static method, short-circuit the rest of the resolver
        $className = $bindArgs['class']->value;
        $methodName = $bindArgs['method']->value;

        if (GraphQLUtils::isAuthorisedToAccess($info, get_class($source), $methodName, $source)) {
            $meth = new ReflectionMethod($className, $methodName);

            if (isset($bindArgs['callingConvention'])) {
                $callingConvention = $bindArgs['callingConvention']->value;
                switch ($callingConvention) {
                    case 'FirstArgCurrentUser':
                        $val = $className::{$methodName}(MyRadio_User::getInstance()->getID());
                        break;
                    case 'FirstArgCurrentObject':
                        // Find the name of the first argument, set that as the source, and pass in the rest to invokeNamed
                        $firstArg = $meth->getParameters()[0];
                        $args[$firstArg->getName()] = $source;
                        $val = GraphQLUtils::invokeNamed($meth, null, $args);
                        break;
                    default:
                        throw new MyRadioException("Unsupported calling convention $callingConvention for static method");
                }

            } else {
                $val = GraphQLUtils::invokeNamed($meth, null, $args);
            }

            return GraphQLUtils::processScalarIfNecessary($info, $val);

        } else {
            $context->addWarning("Unauthorised to access $typeName::$fieldName");
            return GraphQLUtils::returnNullOrThrowForbiddenException($info);
        }
    }
    // Next, check if we're on an array
    if (is_array($source)) {
        if (array_key_exists($fieldName, $source)) {
            if (GraphQLUtils::isAuthorisedToAccess($info, null, null)) {
                return GraphQLUtils::processScalarIfNecessary($info, $source[$fieldName]);
            } else {
                $context->addWarning("Unauthorised to access $typeName::$fieldName");
                return GraphQLUtils::returnNullOrThrowForbiddenException($info);
            }
        } else {
            // We're on an array, but the key we're looking for doesn't exist. No hope of doing the rest
            // of the checks, for fear of returning the array itself.
            // Do an authz check just for the warning, but return null.
            if (GraphQLUtils::isAuthorisedToAccess($info, null, null)) {
                return null;
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
                } elseif (method_exists($source, "getID")) {
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
            if (GraphQLUtils::isAuthorisedToAccess($info, get_class($source), "toDataSource", $source)) {
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
            if (GraphQLUtils::isAuthorisedToAccess($info, get_class($source), $methodName, $source)) {
                // Yay. Call it!
                // (We'll get a ReflectionException here if it's inaccessible. But That's Okay.
                $meth = new ReflectionMethod(
                    // Assume we know what we're doing.
                    get_class($source),
                    $methodName
                );
                // First, though, check if we should be using a calling convention
                if (isset($bindArgs['callingConvention'])) {
                    $callingConvention = $bindArgs['callingConvention']->value;
                    switch ($callingConvention) {
                        case 'FirstArgCurrentUser':
                            $val = $source->{$methodName}(MyRadio_User::getInstance()->getID());
                        break;
                        case 'FirstArgCurrentObject':
                            // Find the name of the first argument, set that as the source, and pass in the rest to invokeNamed
                            $firstArg = $meth->getParameters()[0];
                            $args[$firstArg->getName()] = $source;
                            $val = GraphQLUtils::invokeNamed($meth, $source, $args);
                        break;
                        default:
                            throw new MyRadioException("Unsupported calling convention $callingConvention for dynamic field");
                    }
                } else {
                    $val = GraphQLUtils::invokeNamed($meth, $source, $args);
                }
                return GraphQLUtils::processScalarIfNecessary($info, $val);
            } else {
                // So the method exists, but we can't access it.
                $context->addWarning("Unauthorised to access $typeName::$fieldName");
                return GraphQLUtils::returnNullOrThrowForbiddenException($info);
            }
        }
        // Giving up on methods. Last shot: is it a property?
        if (isset($source->{$fieldName})) {
            // Right. Can we access it?
            if (GraphQLUtils::isAuthorisedToAccess($info, get_class($source), $fieldName, $source)) {
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
    if (GraphQLUtils::isAuthorisedToAccess($info, null, null, $source)) {
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
        $data['operationName'],
        'graphQlResolver'
    );
    $result = $queryResult->toArray($debug);
} catch (Exception $e) {
    $status = $e instanceof MyRadioException ? $e->getCode() : 500;
    $result = [
        'errors' => FormattedError::createFromException($e, $debug)
    ];
}

$warnings = $ctx->getWarnings();
if (count($warnings) > 0) {
    $result['warnings'] = $warnings;
}

$corsWhitelistOrigins = [
    'https://ury.org.uk',
    'http://localhost:3000'
];

$origin = $_SERVER['HTTP_ORIGIN'];
if (in_array($origin, $corsWhitelistOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
}

header('Content-Type: application/json', true, $status);
echo json_encode($result);
