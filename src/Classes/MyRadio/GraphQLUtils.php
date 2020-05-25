<?php


namespace MyRadio\MyRadio;


use GraphQL\Language\AST\DirectiveNode;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\AST\ValueNode;
use GraphQL\Type\Definition\ResolveInfo;

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