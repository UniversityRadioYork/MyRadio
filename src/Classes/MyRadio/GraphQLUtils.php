<?php


namespace MyRadio\MyRadio;


use GraphQL\Language\AST\DirectiveNode;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\AST\ValueNode;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\WrappingType;
use MyRadio\MyRadioException;
use MyRadio\ServiceAPI\MyRadio_Swagger2;

class GraphQLUtils
{
    /**
     * @param ResolveInfo $info
     * @param string $name
     * @return DirectiveNode|null
     */
    public static function getDirectiveByName(ResolveInfo $info, string $name)
    {
        $fieldNode = $info->parentType->getField($info->fieldName)->astNode;
        /** @var NodeList $directives */
        $directives = $fieldNode->directives;
        if ($directives) {
            /** @var DirectiveNode[] $directives */
            foreach ($directives as $directive) {
                if ($directive->name->value === $name) {
                    return $directive;
                }
            }
        }
        return null;
    }

    /**
     * @param DirectiveNode $directive
     * @return ValueNode[]
     */
    public static function getDirectiveArguments(DirectiveNode $directive)
    {
        $args = [];
        foreach ($directive->arguments as $arg) {
            $args[$arg->name->value] = $arg->value;
        }
        return $args;
    }

    /**
     * Er, take a wild guess?
     * @param ResolveInfo $info
     */
    public static function returnNullOrThrowForbiddenException(ResolveInfo $info) {
        if ($info->returnType instanceof NonNull) {
            throw new MyRadioException('Caller cannot access this field', 403);
        } else {
            return null;
        }
    }

    /**
     * Tests if the current caller is authorised to access the given GraphQL field
     * @param ResolveInfo $info
     * @param string $resolvedClass
     * @param string $resolvedMethod
     * @return bool
     */
    public static function isAuthorisedToAccess(ResolveInfo $info, string $resolvedClass, string $resolvedMethod) {
        $caller = MyRadio_Swagger2::getAPICaller();
        if ($caller === null) {
            throw new MyRadioException('No valid authentication data provided', 401);
        }
        // First, check if we have an auth directive. If so, it overrides.
        $authDirective = self::getDirectiveByName($info, 'auth');
        if ($authDirective !== null) {
            $constants = self::getDirectiveArguments($authDirective)['constants'];
            // No constants => public access
            if (count($constants) === 0) {
                return true;
            }
            foreach ($constants as $constant) {
                if (AuthUtils::hasPermission(constant($constant))) {
                    return true;
                }
            }
            return false;
        } else {
            // Object-scalar rule: if we're dealing with an object, use API v2 rules
            // If we're dealing with a scalar, assume it's okay, as it must have come from an object
            if ($info->returnType instanceof WrappingType) {
                $type = $info->returnType->getWrappedType(true);
            } else {
                $type = $info->returnType;
            }
            if ($type instanceof ScalarType || $type instanceof EnumType) {
                return true;
            } else if ($resolvedClass === null && $resolvedMethod === null) {
                return true;
            } else {
                return $caller->canCall($resolvedClass, $resolvedMethod);
            }
        }
    }

    /**
     * Given a resolved value, converts it into a scalar type if appropriate.
     *
     * For example, given a number or date string on a Date/Time/DateTime type field,
     * this method will format it accordingly.
     * @param ResolveInfo $info
     * @param mixed $value
     * @return mixed
     */
    public static function processScalarIfNecessary(ResolveInfo $info, $value) {
        // If the field is not a scalar, don't touch it.
        if ($info->returnType instanceof ScalarType) {
            $type = $info->returnType->name;
        } else if ($info->returnType instanceof WrappingType && $info->returnType->getWrappedType(true) instanceof ScalarType) {
            $type = $info->returnType->getWrappedType(true)->name;
        } else {
            return $value;
        }
        switch ($type) {
            case "Int":
            case "Float":
            case "String":
            case "Boolean":
            case "ID":
            case "HTMLString":
                // Passed through directly
                return $value;
            case "Date":
            case "Time":
            case "DateTime":
                // If the value is a number, assume it's a UNIX timestamp. If not, try and parse it.
                if (is_numeric($value)) {
                    $val_unix = (float) $value;
                } else {
                    $val_unix = strtotime($value);
                    if ($val_unix === false) {
                        throw new MyRadioException("Failed to parse datetime $value");
                    }
                }
                switch ($type) {
                    case "Date":
                        return date("Y-m-d", $val_unix);
                    case "Time":
                        return date("H:i:sP", $val_unix);
                    case "DateTime":
                        return date("Y-m-d\TH:i:sP", $val_unix);
                }
                break;
            case "Duration":
                // If it's a number, assume it's seconds.
                if (is_numeric($value)) {
                    $interval = new \DateInterval("PT${value}S");
                } else {
                    $interval = \DateInterval::createFromDateString($value);
                }
                return $interval->format("H:M:S");
            default:
                throw new MyRadioException("Unknown scalar type $type!");
        }
    }

    public static function invokeNamed(\ReflectionMethod $meth, $object=null, $args=[]) {
        $methArgs = $meth->getParameters();
        // If it has no arguments, we can just invoke it directly.
        if (count($methArgs) === 0) {
            return $meth->invoke($object);
        } else {
            // Overwrite them in the parameters array to ensure we call them in the correct order
            foreach ($methArgs as &$param) {
                $name = $param->getName();
                $param = isset($args[$name]) ? $args[$name] : $param->getDefaultValue();
            }
            return $meth->invokeArgs($object, $methArgs);
        }
    }
}