<?php


namespace MyRadio\MyRadio;

use GraphQL\Language\AST\DirectiveNode;
use GraphQL\Language\AST\EnumValueNode;
use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Language\AST\ValueNode;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\WrappingType;
use MyRadio\MyRadioException;
use MyRadio\ServiceAPI\MyRadio_Season;
use MyRadio\ServiceAPI\MyRadio_Show;
use MyRadio\ServiceAPI\MyRadio_Swagger2;
use MyRadio\ServiceAPI\MyRadio_Timeslot;
use MyRadio\ServiceAPI\MyRadio_User;

class GraphQLUtils
{
    private static $ENUM_MAPPINGS = [
        'OfficerStatus' => [
            'c' => 'Current',
            'h' => 'Historic'
        ],
        'OfficerType' => [
            'h' => 'HeadOfTeam',
            'a' => 'AssistantHeadOfTeam',
            'm' => 'TeamMember',
            'o' => 'Other'
        ]
    ];

    /**
     * @param ResolveInfo $info
     * @param string $name
     * @return DirectiveNode|null
     */
    public static function getDirectiveByName(ResolveInfo $info, string $name)
    {
        $fieldNode = $info->parentType->getField($info->fieldName)->astNode;
        return self::getDirectiveByNameOnAstNode($fieldNode, $name);
    }

    /**
     * @param ObjectTypeDefinitionNode|FieldDefinitionNode $node
     * @param string $name
     * @return DirectiveNode|null
     */
    private static function getDirectiveByNameOnAstNode($node, string $name)
    {
        /** @var NodeList $directives */
        $directives = $node->directives;
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
    public static function returnNullOrThrowForbiddenException(ResolveInfo $info)
    {
        if ($info->returnType instanceof NonNull) {
            throw new MyRadioException('Caller cannot access this field', 403);
        } else {
            return null;
        }
    }

    /**
     * Tests if the current caller is authorised to access the given GraphQL field
     * @param ResolveInfo $info
     * @param string|null $resolvedClass
     * @param string|null $resolvedMethod
     * @param mixed|null $resolvedObject
     * @return bool
     */
    public static function isAuthorisedToAccess(
        ResolveInfo $info,
        $resolvedClass,
        $resolvedMethod,
        $resolvedObject = null
    ) {
        $caller = MyRadio_Swagger2::getAPICaller();
        if (empty($caller)) {
            throw new MyRadioException('No valid authentication data provided', 401);
        }
        if ($caller->hasAuth(AUTH_APISUDO)) {
            // I am become sudo, doer of API calls
            return true;
        }
        // First, check if we have an @auth directive. If so, it overrides.
        $authDirective = self::getDirectiveByName($info, 'auth');
        if ($authDirective !== null) {
            return self::processAuthDirective(
                self::getDirectiveArguments($authDirective),
                $resolvedClass,
                $resolvedMethod,
                $resolvedObject
            );
        } else {
            // Check if there's an @auth on the parent object
            $authDirective = self::getDirectiveByNameOnAstNode($info->parentType->astNode, "auth");
            if ($authDirective !== null) {
                return self::processAuthDirective(
                    self::getDirectiveArguments($authDirective),
                    $resolvedClass,
                    $resolvedMethod,
                    $resolvedObject
                );
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
                } elseif ($resolvedClass === null && $resolvedMethod === null) {
                    return true;
                } else {
                    return $caller->canCall($resolvedClass, $resolvedMethod);
                }
            }
        }
    }

    private static function processAuthDirective(
        array $args,
        $resolvedClass,
        $resolvedMethod,
        $resolvedObject = null
    ) {
        if (isset($args['constants'])) {
            $constants = $args['constants']->values;
            // No constants => public access
            if (count($constants) === 0) {
                return true;
            }
            foreach ($constants as $constant) {
                if (AuthUtils::hasPermission(constant($constant))) {
                    return true;
                }
            }
        }
        /** @var EnumValueNode $hookVal */
        $hook = $args['hook'];
        if (isset($hook)) {
            $hookName = $hook->value;
            switch ($hookName) {
                case 'ViewShow':
                    /** @var MyRadio_Show|MyRadio_Season|MyRadio_Timeslot $show */
                    $show = $resolvedObject;
                    if (AuthUtils::hasPermission(AUTH_VIEWMEMBERSHOWS)) {
                        return true;
                    }
                    return $show->isCurrentUserAnOwner();
                case 'ViewMember':
                case 'ViewOfficer':
                    /** @var MyRadio_User $member */
                    $member = $resolvedObject;
                    if (AuthUtils::hasPermission(AUTH_VIEWOTHERMEMBERS)) {
                        return true;
                    }
                    if ($hookName === 'viewOfficer' && $member->isOfficer()) {
                        return true;
                    }
                    return $member->getID() === (MyRadio_User::getCurrentOrSystemUser()->getID());
                default:
                    throw new MyRadioException("Unknown auth hook $hookName");
            }
        }
        return false;
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
    public static function processScalarIfNecessary(ResolveInfo $info, $value)
    {
        // Resolve enums if necessary
        if ($info->returnType instanceof EnumType) {
            if (isset(self::$ENUM_MAPPINGS[$info->returnType->name])) {
                return self::$ENUM_MAPPINGS[$info->returnType->name][$value];
            }
        }
        // If the field is not a scalar, don't touch it.
        if ($info->returnType instanceof ScalarType) {
            $type = $info->returnType->name;
        } elseif ($info->returnType instanceof WrappingType
            && $info->returnType->getWrappedType(true) instanceof ScalarType
        ) {
            $type = $info->returnType->getWrappedType(true)->name;
        } else {
            return $value;
        }
        // If it has a @coerce directive, do as it says
        $coerceDirective = self::getDirectiveByName($info, 'coerce');
        if ($coerceDirective !== null) {
            foreach (self::getDirectiveArguments($coerceDirective)['hooks']->values as $hook) {
                switch ($hook->value) {
                    case "FalseToNull":
                        if ($value === false) {
                            $value = null;
                        }
                        break;
                    default:
                        $val = $hook->value;
                        throw new MyRadioException("Unknown coerce hook $val");
                }
            }
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
                if ($value === null) {
                    return $value;
                } elseif (is_numeric($value)) {
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
                if ($value === null) {
                    return $value;
                } elseif (is_numeric($value)) {
                    $interval = new \DateInterval("PT${value}S");
                } else {
                    $data = date_parse($value);
                    $interval = new \DateInterval(
                        sprintf(
                            "P%02dY%02dM%02dDT%02dH%02dM%02dS",
                            $data['year'],
                            $data['month'],
                            $data['day'],
                            $data['hour'],
                            $data['minute'],
                            $data['second']
                        )
                    );
                }
                return $interval->format("%H:%M:%S");
            default:
                throw new MyRadioException("Unknown scalar type $type!");
        }
    }

    public static function invokeNamed(\ReflectionMethod $meth, $object = null, $args = [])
    {
        $methArgs = $meth->getParameters();
        // If it has no arguments, we can just invoke it directly.
        if (count($methArgs) === 0) {
            return $meth->invoke($object);
        } else {
            // Overwrite them in the parameters array to ensure we call them in the correct order
            foreach ($methArgs as &$param) {
                $name = $param->getName();
                if (isset($args[$name])) {
                    $param = $args[$name];
                } else {
                    try {
                        $param = $param->getDefaultValue();
                    } catch (\ReflectionException $e) {
                        $methName = $meth->getName();
                        $obj = $meth->getDeclaringClass()->getName();
                        throw new MyRadioException("Missing parameter $name in call to $obj::$methName");
                    }
                }
            }
            return $meth->invokeArgs($object, $methArgs);
        }
    }
}
