<?php

namespace Dedoc\Scramble\Support\OperationExtensions\ParameterExtractor;

use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\TypeTransformer;
use Dedoc\Scramble\Support\OperationExtensions\RequestBodyExtension;
use Dedoc\Scramble\Support\OperationExtensions\RulesExtractor\ParametersExtractionResult;
use Dedoc\Scramble\Support\RouteInfo;
use Dedoc\Scramble\Support\Validation\ConstraintExtractor;
use Dedoc\Scramble\Support\Validation\ConstraintToSchemaConverter;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Extracts parameters from Symfony validated request objects.
 *
 * Analyzes request parameters with Symfony Validator constraints
 * and converts them to OpenAPI schema properties.
 */
class SymfonyValidationParametersExtractor implements ParameterExtractor
{
    public function __construct(
        private ConstraintExtractor $constraintExtractor,
        private ConstraintToSchemaConverter $constraintConverter,
        private TypeTransformer $openApiTransformer,
    ) {}

    public function handle(RouteInfo $routeInfo, array $parameterExtractionResults): array
    {
        $requestClassName = $this->getValidatedRequestClassName($routeInfo);

        if (!$requestClassName || !$this->constraintExtractor->hasConstraints($requestClassName)) {
            return $parameterExtractionResults;
        }

        $parameterExtractionResults[] = $this->extractValidationParameters($requestClassName, $routeInfo);

        return $parameterExtractionResults;
    }

    private function getValidatedRequestClassName(RouteInfo $routeInfo): ?string
    {
        if (!$reflectionAction = $routeInfo->reflectionAction()) {
            return null;
        }

        // Look for a parameter with validation constraints
        foreach ($reflectionAction->getParameters() as $parameter) {
            if ($this->isValidatedRequestParam($parameter)) {
                $type = $parameter->getType();
                if ($type instanceof ReflectionNamedType) {
                    return $type->getName();
                }
            }
        }

        return null;
    }

    private function isValidatedRequestParam(ReflectionParameter $parameter): bool
    {
        if (!$parameter->getType() instanceof ReflectionNamedType) {
            return false;
        }

        $className = $parameter->getType()->getName();

        if (!class_exists($className)) {
            return false;
        }

        // Check if class has validation constraints
        return $this->constraintExtractor->hasConstraints($className);
    }

    private function extractValidationParameters(string $requestClassName, RouteInfo $routeInfo): ParametersExtractionResult
    {
        // TODO: Extract validation groups from route context/attributes if needed
        // For now, extract all constraints (no group filtering)
        $constraints = $this->constraintExtractor->extractFromClass($requestClassName, groups: null);

        $properties = [];
        $required = [];

        foreach ($constraints as $propertyName => $propertyConstraints) {
            // Infer base type from property or use string as default
            $propertySchema = $this->inferPropertySchema($requestClassName, $propertyName);

            // Apply constraints to schema
            $this->constraintConverter->applyConstraints($propertyConstraints, $propertySchema, $propertyName);

            $properties[$propertyName] = $propertySchema;

            // Check if property is required
            if ($this->isPropertyRequired($propertyConstraints)) {
                $required[] = $propertyName;
            }
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
                'required' => $required,
            ],
            schemaName: $this->getSchemaName($requestClassName),
            description: $this->getClassDescription($requestClassName),
        );
    }

    private function inferPropertySchema(string $className, string $propertyName): \Dedoc\Scramble\Support\Generator\Types\Type
    {
        // Try to infer type from property type hint
        try {
            $reflection = new ReflectionClass($className);
            if ($reflection->hasProperty($propertyName)) {
                $property = $reflection->getProperty($propertyName);
                $type = $property->getType();

                if ($type instanceof ReflectionNamedType) {
                    return $this->createSchemaFromType($type->getName());
                }
            }
        } catch (\Exception $e) {
            // Fall through to default
        }

        // Default to string type
        return new \Dedoc\Scramble\Support\Generator\Types\StringType;
    }

    private function createSchemaFromType(string $typeName): \Dedoc\Scramble\Support\Generator\Types\Type
    {
        return match ($typeName) {
            'int', 'integer' => new \Dedoc\Scramble\Support\Generator\Types\IntegerType,
            'float', 'double' => new \Dedoc\Scramble\Support\Generator\Types\NumberType,
            'bool', 'boolean' => new \Dedoc\Scramble\Support\Generator\Types\BooleanType,
            'array' => new \Dedoc\Scramble\Support\Generator\Types\ArrayType,
            default => new \Dedoc\Scramble\Support\Generator\Types\StringType,
        };
    }

    private function isPropertyRequired(array $constraints): bool
    {
        foreach ($constraints as $constraint) {
            if (
                $constraint instanceof \Symfony\Component\Validator\Constraints\NotBlank ||
                $constraint instanceof \Symfony\Component\Validator\Constraints\NotNull
            ) {
                return true;
            }
        }

        return false;
    }

    private function getSchemaName(string $className): ?string
    {
        $parts = explode('\\', $className);
        return end($parts);
    }

    private function getClassDescription(string $className): string
    {
        try {
            $reflection = new ReflectionClass($className);
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
