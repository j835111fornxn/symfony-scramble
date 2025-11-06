<?php

namespace Dedoc\Scramble\Support\TypeToSchemaExtensions;

use Dedoc\Scramble\Extensions\TypeToSchemaExtension;
use Dedoc\Scramble\Support\Generator\Header;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Str;
use Dedoc\Scramble\Support\Type\ArrayItemType_;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\KeyedArrayType;
use Dedoc\Scramble\Support\Type\Literal\LiteralIntegerType;
use Dedoc\Scramble\Support\Type\Literal\LiteralStringType;
use Dedoc\Scramble\Support\Type\NullType;
use Dedoc\Scramble\Support\Type\Type;
use Symfony\Component\HttpFoundation\JsonResponse as SymfonyJsonResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ResponseTypeToSchema extends TypeToSchemaExtension
{
    public function shouldHandle(Type $type)
    {
        if (! $type instanceof Generic || count($type->templateTypes) < 2) {
            return false;
        }

        // Check for Symfony Response classes
        return $type->isInstanceOf(SymfonyResponse::class)
            || $type->isInstanceOf(SymfonyJsonResponse::class);
    }

    /**
     * @param  Generic  $type
     */
    public function toResponse(Type $type)
    {
        $statusCodeType = $type->templateTypes[1];
        if (! $statusCodeType instanceof LiteralIntegerType) {
            return null;
        }

        $contentType = $type->templateTypes[0];
        $emptyContent = $contentType instanceof NullType
            || ($contentType instanceof LiteralStringType && $contentType->value === '');

        $response = Response::make($code = $statusCodeType->value)
            ->description($code === 204 ? 'No content' : '');

        if (! $emptyContent) {
            $response->setContent(
                'application/json',
                Schema::fromType($this->openApiTransformer->transform($type->templateTypes[0])),
            );
        }

        $this->addHeaders($response, $type);

        return $response;
    }

    private function addHeaders(Response $response, Generic $type): void
    {
        $headersType = $type->templateTypes[2] ?? null;

        if (! $headersType instanceof KeyedArrayType) {
            return;
        }

        foreach ($headersType->items as $item) {
            $this->addHeader($response, $item);
        }
    }

    private function addHeader(Response $response, ArrayItemType_ $item): void
    {
        if (! $key = $this->getNormalizedHeaderKey($item)) {
            return;
        }

        if (Str::lower($key) === 'content-type') {
            $this->handleContentTypeHeader($response, $item);

            return;
        }

        $response->addHeader(
            $key,
            new Header(schema: Schema::fromType($this->openApiTransformer->transform($item->value))),
        );
    }

    private function handleContentTypeHeader(Response $response, ArrayItemType_ $item): void
    {
        $contentTypeValue = $item->value instanceof LiteralStringType ? $item->value->value : null;

        if (! $contentTypeValue) {
            return;
        }

        if (! $firstMediaType = array_keys($response->content)[0] ?? null) {
            return;
        }

        $mediaType = $response->getContent($firstMediaType);

        unset($response->content[$firstMediaType]);
        $response->content[$contentTypeValue] = $mediaType;
    }

    private function getNormalizedHeaderKey(ArrayItemType_ $item): ?string
    {
        $key = $item->key ?: ($item->keyType instanceof LiteralStringType ? $item->keyType->value : null);

        return is_string($key) ? $key : null;
    }
}
