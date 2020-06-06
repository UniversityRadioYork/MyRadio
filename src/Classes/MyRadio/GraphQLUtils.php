<?php


namespace MyRadio\MyRadio;


use GraphQL\Language\AST\DirectiveNode;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\AST\ValueNode;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\ScalarType;
use MyRadio\MyRadioException;

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
        } else if ($info->returnType instanceof NonNull && $info->returnType->ofType instanceof ScalarType) {
            $type = $info->returnType->ofType->name;
        } else {
            return $value;
        }
        switch ($type) {
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