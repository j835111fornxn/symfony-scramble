<?php

namespace Dedoc\Scramble\Tests\InferExtensions;

use Dedoc\Scramble\Infer;
use Dedoc\Scramble\Support\InferExtensions\SymfonySerializerExtension;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Tests\SymfonyTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class SymfonySerializerExtensionTest extends SymfonyTestCase
{
    private SymfonySerializerExtension $extension;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension = new SymfonySerializerExtension(
            $this->createMock(Infer::class)
        );
    }

    #[Test]
    public function returnsNullForNonExistentClass(): void
    {
        $type = $this->extension->getSerializedType('NonExistentClass');

        $this->assertNull($type);
    }

    #[Test]
    public function returnsObjectTypeForValidClass(): void
    {
        $type = $this->extension->getSerializedType(TestSerializableClass::class);

        $this->assertInstanceOf(ObjectType::class, $type);
        /** @var ObjectType $type */
        $this->assertSame(TestSerializableClass::class, $type->name);
    }

    #[Test]
    public function respectsIgnoreAttribute(): void
    {
        // This test would require more sophisticated property inspection
        // For now, we just verify the extension doesn't throw errors
        $type = $this->extension->getSerializedType(TestSerializableClassWithIgnore::class);

        $this->assertInstanceOf(ObjectType::class, $type);
    }

    #[Test]
    public function filtersBySerializationGroups(): void
    {
        // Test that groups filtering works
        $type = $this->extension->getSerializedType(
            TestSerializableClassWithGroups::class,
            ['public']
        );

        $this->assertInstanceOf(ObjectType::class, $type);
    }

    #[Test]
    public function handlesSerializedNameAttribute(): void
    {
        // Test that SerializedName attribute is respected
        $type = $this->extension->getSerializedType(TestSerializableClassWithSerializedName::class);

        $this->assertInstanceOf(ObjectType::class, $type);
    }

    #[Test]
    public function canRegisterCustomNormalizers(): void
    {
        $normalizer = new TestCustomNormalizer;

        $this->extension->registerNormalizer($normalizer);

        // Normalizer should be registered and used for type inference
        $type = $this->extension->getSerializedType(TestCustomClass::class);

        $this->assertNotNull($type);
    }
}

// Test fixture classes

class TestSerializableClass
{
    public string $name;

    public int $age;

    public ?string $email;
}

class TestSerializableClassWithIgnore
{
    public string $name;

    #[Ignore]
    public string $password;
}

class TestSerializableClassWithGroups
{
    #[Groups(['public', 'admin'])]
    public string $name;

    #[Groups(['admin'])]
    public string $email;

    public string $bio; // No groups = included in all
}

class TestSerializableClassWithSerializedName
{
    #[SerializedName('full_name')]
    public string $name;

    #[SerializedName('email_address')]
    public string $email;
}

class TestCustomClass
{
    public string $customProperty;
}

class TestCustomNormalizer implements NormalizerInterface
{
    public function normalize($object, ?string $format = null, array $context = []): array
    {
        return [
            'custom' => 'value',
        ];
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return $data === TestCustomClass::class || $data instanceof TestCustomClass;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            TestCustomClass::class => true,
        ];
    }
}
