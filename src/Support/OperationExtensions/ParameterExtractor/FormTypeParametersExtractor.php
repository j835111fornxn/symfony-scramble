<?php

namespace Dedoc\Scramble\Support\OperationExtensions\ParameterExtractor;

use Dedoc\Scramble\Support\Generator\TypeTransformer;
use Dedoc\Scramble\Support\OperationExtensions\RequestBodyExtension;
use Dedoc\Scramble\Support\OperationExtensions\RulesExtractor\ParametersExtractionResult;
use Dedoc\Scramble\Support\RouteInfo;
use Dedoc\Scramble\Support\Forms\FormTypeExtractor;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Extracts parameters from Symfony Form types.
 *
 * Analyzes controller method parameters that are Form types
 * and converts them to OpenAPI schema properties.
 */
class FormTypeParametersExtractor implements ParameterExtractor
{
    public function __construct(
        private FormTypeExtractor $formExtractor,
        private TypeTransformer $openApiTransformer,
    ) {}

    public function handle(RouteInfo $routeInfo, array $parameterExtractionResults): array
    {
        $formTypeClassName = $this->getFormTypeClassName($routeInfo);

        if (!$formTypeClassName || !$this->formExtractor->isFormType($formTypeClassName)) {
            return $parameterExtractionResults;
        }

        $parameterExtractionResults[] = $this->extractFormTypeParameters($formTypeClassName, $routeInfo);

        return $parameterExtractionResults;
    }

    private function getFormTypeClassName(RouteInfo $routeInfo): ?string
    {
        if (!$reflectionAction = $routeInfo->reflectionAction()) {
            return null;
        }

        // Look for a parameter that is a Form type
        foreach ($reflectionAction->getParameters() as $parameter) {
            if ($this->isFormTypeParam($parameter)) {
                $type = $parameter->getType();
                if ($type instanceof ReflectionNamedType) {
                    return $type->getName();
                }
            }
        }

        return null;
    }

    private function isFormTypeParam(ReflectionParameter $parameter): bool
    {
        if (!$parameter->getType() instanceof ReflectionNamedType) {
            return false;
        }

        $className = $parameter->getType()->getName();

        if (!class_exists($className)) {
            return false;
        }

        // Check if class is a Form type
        return $this->formExtractor->isFormType($className);
    }

    private function extractFormTypeParameters(string $formTypeClassName, RouteInfo $routeInfo): ParametersExtractionResult
    {
        $formData = $this->formExtractor->extractFromFormType($formTypeClassName);

        $properties = [];

        foreach ($formData['properties'] as $fieldName => $fieldSchema) {
            // Convert array schema to Type objects
            $properties[$fieldName] = $this->arrayToType($fieldSchema);
        }

        // Determine if parameters should be in body or query
        $in = in_array(
            mb_strtolower($routeInfo->route->methods()[0] ?? 'GET'),
            RequestBodyExtension::HTTP_METHODS_WITHOUT_REQUEST_BODY
        ) ? 'query' : 'body';

        return new ParametersExtractionResult(
            parameters: [
                'in' => $in,
                'properties' => $properties,
                'required' => $formData['required'],
            ],
            schemaName: $this->getSchemaName($formTypeClassName),
            description: $this->getClassDescription($formTypeClassName),
        );
    }

    /**
     * Convert array schema to Type object.
     */
    private function arrayToType(array $schema): \Dedoc\Scramble\Support\Generator\Types\Type
    {
        $type = match ($schema['type'] ?? 'string') {
            'string' => new \Dedoc\Scramble\Support\Generator\Types\StringType,
            'integer' => new \Dedoc\Scramble\Support\Generator\Types\IntegerType,
            'number' => new \Dedoc\Scramble\Support\Generator\Types\NumberType,
            'boolean' => new \Dedoc\Scramble\Support\Generator\Types\BooleanType,
            'array' => new \Dedoc\Scramble\Support\Generator\Types\ArrayType,
            'object' => new \Dedoc\Scramble\Support\Generator\Types\ObjectType,
            default => new \Dedoc\Scramble\Support\Generator\Types\StringType,
        };

        // Apply format if present
        if (isset($schema['format'])) {
            $type->format = $schema['format'];
        }

        // Apply enum if present
        if (isset($schema['enum'])) {
            $type->enum = $schema['enum'];
        }

        // Handle nested properties for objects
        if ($type instanceof \Dedoc\Scramble\Support\Generator\Types\ObjectType && isset($schema['properties'])) {
            foreach ($schema['properties'] as $propName => $propSchema) {
                $type->addProperty($propName, $this->arrayToType($propSchema));
            }

            if (isset($schema['required'])) {
                $type->required = $schema['required'];
            }
        }

        // Handle array items
        if ($type instanceof \Dedoc\Scramble\Support\Generator\Types\ArrayType && isset($schema['items'])) {
            $type->setItems($this->arrayToType($schema['items']));
        }

        return $type;
    }

    private function getSchemaName(string $className): ?string
    {
        $parts = explode('\\', $className);
        return end($parts);
    }

    private function getClassDescription(string $className): string
    {
        try {
            $reflection = new \ReflectionClass($className);
            $docComment = $reflection->getDocComment();

            if ($docComment) {
                // Extract first line of doc comment as description
                preg_match('/\/\*\*\s*\n\s*\*\s*(.+?)\n/', $docComment, $matches);
                return $matches[1] ?? '';
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return '';
    }
}
