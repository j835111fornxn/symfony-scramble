<?php

namespace Dedoc\Scramble\Support\InferExtensions;

use Dedoc\Scramble\Infer\Context;
use Dedoc\Scramble\Infer\Services\ReferenceTypeResolver;
use Dedoc\Scramble\Support\Type\ArrayType;
use Dedoc\Scramble\Support\Type\BooleanType;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\IntegerType;
use Dedoc\Scramble\Support\Type\KeyedArrayType;
use Dedoc\Scramble\Support\Type\NullType;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\StringType;
use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Support\Type\Union;
use PhpParser\Node;
use Symfony\Component\Form\Extension\Core\Type as FormType;
use Symfony\Component\Form\FormInterface;

/**
 * Infers types from Symfony Form classes.
 * Converts Form field definitions to appropriate Scramble types.
 */
class SymfonyFormExtension
{
    public function __construct(private \Dedoc\Scramble\Infer $infer) {}

    /**
     * Map of Symfony Form types (as strings) to Scramble types.
     * Using strings instead of ::class to avoid dependency on symfony/form package.
     */
    private const TYPE_MAP = [
        'Symfony\\Component\\Form\\Extension\\Core\\Type\\TextType' => StringType::class,
        'Symfony\\Component\\Form\\Extension\\Core\\Type\\EmailType' => StringType::class,
        'Symfony\\Component\\Form\\Extension\\Core\\Type\\PasswordType' => StringType::class,
        'Symfony\\Component\\Form\\Extension\\Core\\Type\\TextareaType' => StringType::class,
        'Symfony\\Component\\Form\\Extension\\Core\\Type\\UrlType' => StringType::class,
        'Symfony\\Component\\Form\\Extension\\Core\\Type\\SearchType' => StringType::class,
        'Symfony\\Component\\Form\\Extension\\Core\\Type\\TelType' => StringType::class,
        'Symfony\\Component\\Form\\Extension\\Core\\Type\\ColorType' => StringType::class,

        'Symfony\\Component\\Form\\Extension\\Core\\Type\\IntegerType' => IntegerType::class,
        'Symfony\\Component\\Form\\Extension\\Core\\Type\\NumberType' => IntegerType::class,
        'Symfony\\Component\\Form\\Extension\\Core\\Type\\RangeType' => IntegerType::class,
        'Symfony\\Component\\Form\\Extension\\Core\\Type\\PercentType' => IntegerType::class,

        'Symfony\\Component\\Form\\Extension\\Core\\Type\\MoneyType' => StringType::class,
        'Symfony\\Component\\Form\\Extension\\Core\\Type\\CurrencyType' => StringType::class,

        'Symfony\\Component\\Form\\Extension\\Core\\Type\\CheckboxType' => BooleanType::class,

        'Symfony\\Component\\Form\\Extension\\Core\\Type\\DateType' => StringType::class,
        'Symfony\\Component\\Form\\Extension\\Core\\Type\\DateTimeType' => StringType::class,
        'Symfony\\Component\\Form\\Extension\\Core\\Type\\TimeType' => StringType::class,
        'Symfony\\Component\\Form\\Extension\\Core\\Type\\BirthdayType' => StringType::class,

        'Symfony\\Component\\Form\\Extension\\Core\\Type\\ChoiceType' => StringType::class, // or array for multiple
        'Symfony\\Component\\Form\\Extension\\Core\\Type\\EntityType' => IntegerType::class, // entity ID
        'Symfony\\Component\\Form\\Extension\\Core\\Type\\CountryType' => StringType::class,
        'Symfony\\Component\\Form\\Extension\\Core\\Type\\LanguageType' => StringType::class,
        'Symfony\\Component\\Form\\Extension\\Core\\Type\\LocaleType' => StringType::class,
        'Symfony\\Component\\Form\\Extension\\Core\\Type\\TimezoneType' => StringType::class,

        'Symfony\\Component\\Form\\Extension\\Core\\Type\\FileType' => StringType::class, // file path or upload

        'Symfony\\Component\\Form\\Extension\\Core\\Type\\CollectionType' => ArrayType::class,
        'Symfony\\Component\\Form\\Extension\\Core\\Type\\RepeatedType' => StringType::class,
    ];

    public function shouldHandle(Node\Expr $node): bool
    {
        if (!$node instanceof Node\Expr\MethodCall) {
            return false;
        }

        // Handle $formBuilder->add() calls
        if ($node->name instanceof Node\Identifier && $node->name->name === 'add') {
            $varType = $this->infer->getType($node->var);

            if ($varType instanceof ObjectType) {
                return $varType->isInstanceOf('Symfony\Component\Form\FormBuilderInterface');
            }
        }

        return false;
    }

    public function infer(Node\Expr $node, Context $context): ?Type
    {
        if (!$node instanceof Node\Expr\MethodCall) {
            return null;
        }

        // Get the field type from the second argument
        if (count($node->args) < 2) {
            return null;
        }

        $typeArg = $node->args[1]->value;

        // Handle ::class constants
        if (
            $typeArg instanceof Node\Expr\ClassConstFetch
            && $typeArg->name instanceof Node\Identifier
            && $typeArg->name->name === 'class'
        ) {
            $formTypeClass = $this->resolveClassName($typeArg->class, $context);

            if ($formTypeClass) {
                return $this->inferFromFormType($formTypeClass, $node->args[2] ?? null, $context);
            }
        }

        return null;
    }

    /**
     * Infer Scramble type from Symfony Form type class.
     */
    private function inferFromFormType(string $formTypeClass, ?Node\Arg $optionsArg, Context $context): ?Type
    {
        // Map Symfony form type to Scramble type
        $scrambleTypeClass = self::TYPE_MAP[$formTypeClass] ?? null;

        if (!$scrambleTypeClass) {
            // For custom form types, try to infer from the class itself
            return $this->inferFromCustomFormType($formTypeClass, $optionsArg, $context);
        }

        $type = new $scrambleTypeClass();

        // Handle options that affect the type
        if ($optionsArg) {
            $type = $this->applyFormOptions($type, $formTypeClass, $optionsArg, $context);
        }

        return $type;
    }

    /**
     * Apply form options to modify the inferred type.
     */
    private function applyFormOptions(Type $type, string $formTypeClass, Node\Arg $optionsArg, Context $context): Type
    {
        $optionsValue = $optionsArg->value;

        if (!$optionsValue instanceof Node\Expr\Array_) {
            return $type;
        }

        $options = $this->parseArrayOptions($optionsValue, $context);

        // Handle 'required' option
        if (isset($options['required']) && $options['required'] === false) {
            // Type can be null
            $type = Union::wrap($type, new NullType());
        }

        // Handle 'multiple' option for ChoiceType
        if ($formTypeClass === 'Symfony\\Component\\Form\\Extension\\Core\\Type\\ChoiceType' && isset($options['multiple']) && $options['multiple'] === true) {
            $type = new ArrayType($type);
        }

        // Handle 'choices' option for ChoiceType to get enum values
        if ($formTypeClass === 'Symfony\\Component\\Form\\Extension\\Core\\Type\\ChoiceType' && isset($options['choices'])) {
            // Could create an enum type here with specific values
            // For now, keep as StringType
        }

        return $type;
    }

    /**
     * Parse array options from Array_ node.
     */
    private function parseArrayOptions(Node\Expr\Array_ $arrayNode, Context $context): array
    {
        $options = [];

        foreach ($arrayNode->items as $item) {
            if (!$item) {
                continue;
            }

            $key = null;
            if ($item->key instanceof Node\Scalar\String_) {
                /** @var Node\Scalar\String_ $keyNode */
                $keyNode = $item->key;
                $key = $keyNode->value;
            }

            if (!$key) {
                continue;
            }

            // Try to get literal values
            $value = $this->getLiteralValue($item->value);

            if ($value !== null) {
                $options[$key] = $value;
            }
        }

        return $options;
    }

    /**
     * Get literal value from node.
     */
    private function getLiteralValue(Node\Expr $node): mixed
    {
        return match (true) {
            $node instanceof Node\Scalar\String_ => $node->value,
            $node instanceof Node\Scalar\LNumber => $node->value,
            $node instanceof Node\Scalar\DNumber => $node->value,
            $node instanceof Node\Expr\ConstFetch && $node->name->toString() === 'true' => true,
            $node instanceof Node\Expr\ConstFetch && $node->name->toString() === 'false' => false,
            $node instanceof Node\Expr\ConstFetch && $node->name->toString() === 'null' => null,
            default => null,
        };
    }

    /**
     * Infer type from custom form type class.
     */
    private function inferFromCustomFormType(string $formTypeClass, ?Node\Arg $optionsArg, Context $context): ?Type
    {
        // For custom form types, we could analyze the buildForm method
        // to understand the structure
        // For now, return a generic ObjectType

        try {
            $reflection = new \ReflectionClass($formTypeClass);

            // If the form type has a data_class option, use that
            if ($reflection->hasMethod('configureOptions')) {
                // Could parse configureOptions to find data_class
                // For now, return ObjectType with the form type class
            }
        } catch (\ReflectionException $e) {
            // Class doesn't exist or can't be reflected
        }

        return new ObjectType($formTypeClass);
    }

    /**
     * Resolve class name from node.
     */
    private function resolveClassName(Node\Name|Node\Expr $node, Context $context): ?string
    {
        if ($node instanceof Node\Name) {
            // Resolve the fully qualified class name
            return $node->toString();
        }

        return null;
    }
}
