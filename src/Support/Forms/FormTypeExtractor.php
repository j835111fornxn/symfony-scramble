<?php

namespace Dedoc\Scramble\Support\Forms;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;

/**
 * Extracts metadata from Symfony Form types for OpenAPI schema generation.
 *
 * Analyzes form fields, their types, constraints, and nested forms to build
 * request body schemas.
 */
class FormTypeExtractor
{
    public function __construct(
        private readonly FormFactoryInterface $formFactory,
    ) {}

    /**
     * Extract schema information from a Form type class.
     *
     * @param  class-string  $formTypeClass
     * @return array{properties: array, required: array<string>, nested: array}
     */
    public function extractFromFormType(string $formTypeClass): array
    {
        if (! class_exists($formTypeClass)) {
            return ['properties' => [], 'required' => [], 'nested' => []];
        }

        try {
            // Create a form instance
            $form = $this->formFactory->create($formTypeClass);
        } catch (\Exception $e) {
            return ['properties' => [], 'required' => [], 'nested' => []];
        }

        return $this->extractFromForm($form);
    }

    /**
     * Extract schema information from a Form instance.
     *
     * @return array{properties: array, required: array<string>, nested: array}
     */
    public function extractFromForm(FormInterface $form): array
    {
        $properties = [];
        $required = [];
        $nested = [];

        foreach ($form->all() as $name => $child) {
            $fieldInfo = $this->extractFieldInfo($child);

            $properties[$name] = $fieldInfo['schema'];

            if ($fieldInfo['required']) {
                $required[] = $name;
            }

            if ($fieldInfo['is_nested']) {
                $nested[$name] = $fieldInfo['nested_type'];
            }
        }

        return [
            'properties' => $properties,
            'required' => $required,
            'nested' => $nested,
        ];
    }

    /**
     * Extract information about a single form field.
     *
     * @return array{schema: array, required: bool, is_nested: bool, nested_type: ?string}
     */
    private function extractFieldInfo(FormInterface $field): array
    {
        $config = $field->getConfig();
        $type = $config->getType();
        $innerType = $type->getInnerType();

        $required = $config->getOption('required', false);
        $isNested = false;
        $nestedType = null;

        // Determine the schema type based on the form field type
        $schema = $this->mapFormTypeToSchema($innerType::class, $config);

        // Check if this is a nested form
        if ($innerType instanceof \Symfony\Component\Form\Extension\Core\Type\FormType) {
            $isNested = true;
            $nestedType = $innerType::class;

            // Recursively extract nested form structure
            $nestedData = $this->extractFromForm($field);
            $schema = [
                'type' => 'object',
                'properties' => $nestedData['properties'],
            ];
            if (! empty($nestedData['required'])) {
                $schema['required'] = $nestedData['required'];
            }
        }

        // Check if this is a collection type (array of forms)
        if ($innerType instanceof \Symfony\Component\Form\Extension\Core\Type\CollectionType) {
            $isNested = true;
            $entryType = $config->getOption('entry_type');
            $nestedType = $entryType;

            // Create a temporary form to extract the entry structure
            try {
                $entryForm = $this->formFactory->create($entryType);
                $entryData = $this->extractFromForm($entryForm);

                $schema = [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => $entryData['properties'],
                    ],
                ];

                if (! empty($entryData['required'])) {
                    $schema['items']['required'] = $entryData['required'];
                }

                // Handle collection constraints (min/max entries)
                if ($config->hasOption('allow_add') && $config->getOption('allow_add')) {
                    // Collection is dynamic
                }
            } catch (\Exception $e) {
                // Fallback to basic array
                $schema = ['type' => 'array', 'items' => ['type' => 'object']];
            }
        }

        return [
            'schema' => $schema,
            'required' => $required,
            'is_nested' => $isNested,
            'nested_type' => $nestedType,
        ];
    }

    /**
     * Map Symfony form field types to OpenAPI schema types.
     *
     * @param  class-string  $formTypeClass
     */
    private function mapFormTypeToSchema(string $formTypeClass, $config): array
    {
        $schema = match ($formTypeClass) {
            \Symfony\Component\Form\Extension\Core\Type\TextType::class,
            \Symfony\Component\Form\Extension\Core\Type\TextareaType::class,
            \Symfony\Component\Form\Extension\Core\Type\EmailType::class,
            \Symfony\Component\Form\Extension\Core\Type\UrlType::class,
            \Symfony\Component\Form\Extension\Core\Type\PasswordType::class,
            \Symfony\Component\Form\Extension\Core\Type\SearchType::class,
            \Symfony\Component\Form\Extension\Core\Type\TelType::class,
            \Symfony\Component\Form\Extension\Core\Type\ColorType::class => ['type' => 'string'],

            \Symfony\Component\Form\Extension\Core\Type\IntegerType::class => ['type' => 'integer'],

            \Symfony\Component\Form\Extension\Core\Type\NumberType::class,
            \Symfony\Component\Form\Extension\Core\Type\MoneyType::class,
            \Symfony\Component\Form\Extension\Core\Type\PercentType::class => ['type' => 'number'],

            \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class => ['type' => 'boolean'],

            \Symfony\Component\Form\Extension\Core\Type\DateType::class,
            \Symfony\Component\Form\Extension\Core\Type\BirthdayType::class => [
                'type' => 'string',
                'format' => 'date',
            ],

            \Symfony\Component\Form\Extension\Core\Type\DateTimeType::class,
            \Symfony\Component\Form\Extension\Core\Type\DateIntervalType::class => [
                'type' => 'string',
                'format' => 'date-time',
            ],

            \Symfony\Component\Form\Extension\Core\Type\TimeType::class => [
                'type' => 'string',
                'format' => 'time',
            ],

            \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class => $this->extractChoiceSchema($config),

            \Symfony\Component\Form\Extension\Core\Type\FileType::class => [
                'type' => 'string',
                'format' => 'binary',
            ],

            default => ['type' => 'string'], // Default to string
        };

        // Apply format-specific attributes
        if ($formTypeClass === \Symfony\Component\Form\Extension\Core\Type\EmailType::class) {
            $schema['format'] = 'email';
        } elseif ($formTypeClass === \Symfony\Component\Form\Extension\Core\Type\UrlType::class) {
            $schema['format'] = 'uri';
        }

        return $schema;
    }

    /**
     * Extract schema for choice fields (select, radio, etc.).
     */
    private function extractChoiceSchema($config): array
    {
        $schema = ['type' => 'string'];

        try {
            $choices = $config->getOption('choices');
            if (! empty($choices)) {
                // Extract the actual choice values
                $schema['enum'] = array_values($choices);
            }

            $multiple = $config->getOption('multiple', false);
            if ($multiple) {
                $schema = [
                    'type' => 'array',
                    'items' => $schema,
                ];
            }
        } catch (\Exception $e) {
            // Ignore and return basic schema
        }

        return $schema;
    }

    /**
     * Check if a class is a Form type.
     *
     * @param  class-string  $className
     */
    public function isFormType(string $className): bool
    {
        if (! class_exists($className)) {
            return false;
        }

        try {
            $reflection = new \ReflectionClass($className);

            return $reflection->implementsInterface(FormTypeInterface::class) ||
                $reflection->isSubclassOf(\Symfony\Component\Form\AbstractType::class);
        } catch (\Exception $e) {
            return false;
        }
    }
}
