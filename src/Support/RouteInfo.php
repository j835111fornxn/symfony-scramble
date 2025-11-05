<?php

namespace Dedoc\Scramble\Support;

use Closure;
use Dedoc\Scramble\Infer;
use Dedoc\Scramble\Infer\Reflector\ClosureReflector;
use Dedoc\Scramble\Infer\Reflector\MethodReflector;
use Dedoc\Scramble\Support\IndexBuilders\Bag;
use Dedoc\Scramble\Support\IndexBuilders\RequestParametersBuilder;
use Dedoc\Scramble\Support\IndexBuilders\ScopeCollector;
use Dedoc\Scramble\Support\OperationExtensions\ParameterExtractor\InferredParameter;
use Dedoc\Scramble\Support\Type\FunctionType;
use Illuminate\Routing\Route;
use Laravel\SerializableClosure\Support\ReflectionClosure;
use LogicException;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;

class RouteInfo
{
    protected ?Infer\Definition\FunctionLikeDefinition $actionDefinition = null;

    public ?FunctionType $methodType = null;

    private ?PhpDocNode $phpDoc = null;

    private ?ClassMethod $methodNode = null;

    private ?FunctionLike $actionNode = null;

    private ?Infer\Scope\Scope $scope = null;

    /** @var Bag<array<string, InferredParameter>> */
    public readonly Bag $requestParametersFromCalls;

    public readonly Infer\Extensions\IndexBuildingBroker $indexBuildingBroker;

    public function __construct(
        public readonly Route|RouteAdapter $route,
        private Infer $infer, // @phpstan-ignore property.onlyWritten
    ) {
        /** @var Bag<array<string, InferredParameter>> $bag */
        $bag = new Bag;
        $this->requestParametersFromCalls = $bag;
        $this->indexBuildingBroker = app(Infer\Extensions\IndexBuildingBroker::class);
    }

    public function isClassBased(): bool
    {
        $uses = $this->route->getAction('uses');
        return is_string($uses) || is_array($uses);
    }

    public function className(): ?string
    {
        if (!$this->isClassBased()) {
            return null;
        }

        $uses = $this->route->getAction('uses');

        if (is_array($uses) && isset($uses[0])) {
            // Symfony format: [ControllerClass::class, 'method']
            return is_object($uses[0]) ? get_class($uses[0]) : ltrim($uses[0], '\\');
        }

        if (is_string($uses)) {
            // Laravel format: "Controller@method" or Symfony format: "Controller::method"
            $separator = str_contains($uses, '@') ? '@' : '::';
            return ltrim(explode($separator, $uses)[0], '\\');
        }

        return null;
    }

    public function methodName(): ?string
    {
        if (!$this->isClassBased()) {
            return null;
        }

        $uses = $this->route->getAction('uses');

        if (is_array($uses) && isset($uses[1])) {
            // Symfony format: [ControllerClass::class, 'method']
            return $uses[1];
        }

        if (is_string($uses)) {
            // Laravel format: "Controller@method" or Symfony format: "Controller::method"
            $separator = str_contains($uses, '@') ? '@' : '::';
            $parts = explode($separator, $uses);
            return $parts[1] ?? null;
        }

        return null;
    }

    public function phpDoc(): PhpDocNode
    {
        if ($this->phpDoc) {
            return $this->phpDoc;
        }

        if (! $this->actionNode()) {
            return new PhpDocNode([]);
        }

        return $this->phpDoc = $this->actionNode()->getAttribute('parsedPhpDoc') ?: new PhpDocNode([]); // @phpstan-ignore return.type
    }

    /**
     * @deprecated use `actionNode` instead
     */
    public function methodNode(): ?ClassMethod
    {
        if ($this->methodNode || ! $this->isClassBased() || ! $this->reflectionMethod()) {
            return $this->methodNode;
        }

        $methodNode = $this->getActionReflector()->getAstNode();
        if (! $methodNode instanceof ClassMethod) {
            throw new LogicException('ClassMethod node expected from method reflector');
        }

        return $this->methodNode = $methodNode;
    }

    protected function closureNode(): ?FunctionLike
    {
        if ($this->actionNode || $this->isClassBased()) {
            return $this->actionNode;
        }

        return $this->actionNode = $this->getActionReflector()->getAstNode();
    }

    public function actionNode(): ?FunctionLike
    {
        return $this->isClassBased() ? $this->methodNode() : $this->closureNode(); // @phpstan-ignore method.deprecated
    }

    public function reflectionAction(): ReflectionMethod|ReflectionClosure|null
    {
        return $this->isClassBased() ? $this->reflectionMethod() : $this->reflectionClosure();
    }

    public function reflectionClosure(): ?ReflectionClosure
    {
        if ($this->isClassBased()) {
            return null;
        }

        $uses = $this->route->getAction('uses');

        if (! $uses instanceof Closure) {
            return null;
        }

        if (! class_exists(ReflectionClosure::class)) {
            return null;
        }

        return new ReflectionClosure($uses);
    }

    public function reflectionMethod(): ?ReflectionMethod
    {
        if (! $this->isClassBased()) {
            return null;
        }

        if (! method_exists($this->className(), $this->methodName())) {
            return null;
        }

        return (new ReflectionClass($this->className()))
            ->getMethod($this->methodName());
    }

    public function getReturnType()
    {
        return (new RouteResponseTypeRetriever($this))->getResponseType();
    }

    public function getActionReflector(): MethodReflector|ClosureReflector
    {
        if ($this->isClassBased()) {
            $className = $this->className();
            $methodName = $this->methodName();

            if ($className && $methodName) {
                return MethodReflector::make($className, $methodName);
            }
        }

        $uses = $this->route->getAction('uses');
        if ($uses instanceof Closure) {
            return ClosureReflector::make($uses);
        }

        throw new LogicException('Cannot determine the action reflector');
    }

    public function getActionDefinition(): ?Infer\Definition\FunctionLikeDefinition
    {
        if ($this->actionDefinition) {
            return $this->actionDefinition;
        }

        $scopeCollector = new ScopeCollector;

        $this->actionDefinition = $this->getActionReflector()->getFunctionLikeDefinition(
            indexBuilders: [
                new RequestParametersBuilder($this->requestParametersFromCalls),
                $scopeCollector,
                ...$this->indexBuildingBroker->indexBuilders,
            ],
            withSideEffects: true,
        );

        $this->scope = $scopeCollector->getScope($this->actionDefinition);

        return $this->actionDefinition;
    }

    public function getActionType(): ?FunctionType
    {
        return $this->getActionDefinition()?->type;
    }

    /**
     * @deprecated use `getActionType`
     *
     * @todo Maybe better name is needed as this method performs method analysis, indexes building, etc.
     */
    public function getMethodType(): ?FunctionType
    {
        return $this->getActionType();
    }

    /** @internal */
    public function getScope(): Infer\Scope\Scope
    {
        $this->getActionDefinition();

        if (! $this->scope) {
            throw new RuntimeException('Scope is not initialized for route.');
        }

        return $this->scope;
    }
}
