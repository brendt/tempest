<?php

declare(strict_types=1);

namespace Tempest\Generation;

use Closure;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;
use function Tempest\Support\str;

/**
 * @internal
 */
trait ManipulatesPhpClasses
{
    use SimplifiesClassNames;

    protected ClassType $classType;

    protected PhpFile $file;

    protected string $namespace;

    /** @var array<Closure> */
    protected array $manipulations = [];

    public function addMethod(
        string $name,
        string $body = '',
        array $parameters = [],
        string $returnType = 'void',
    ): self {
        $method = $this->classType
            ->addMethod($name, overwrite: true)
            ->setReturnType($returnType)
            ->setBody($body);

        foreach ($parameters as $parameter => $type) {
            $method
                ->addParameter($parameter)
                ->setType($type);
        }

        return $this;
    }

    public function manipulate(Closure $callback): self
    {
        $this->manipulations[] = $callback;

        return $this;
    }

    public function setClassName(string $name): self
    {
        $this->classType->setName($name);

        return $this;
    }

    public function removeMethod(string $name): self
    {
        $this->classType->removeMethod($name);

        return $this;
    }

    public function addMethodAttribute(string $method, string $attribute, array $arguments = []): self
    {
        $method = $this->classType->getMethod($method);
        $method->addAttribute($attribute, $arguments);

        return $this;
    }

    public function addClassAttribute(string $attributeName, array $arguments = []): self
    {
        $this->classType->addAttribute($attributeName, $arguments);

        return $this;
    }

    public function removeClassAttribute(string $attributeName): self
    {
        $attributes = $this->classType->getAttributes();

        foreach ($attributes as $key => $attribute) {
            if ($attribute->getName() === $attributeName) {
                unset($attributes[$key]);
            }
        }

        $this->classType->setAttributes($attributes);

        return $this;
    }

    public function setStrictTypes(bool $strictTypes = true): self
    {
        $this->file->setStrictTypes($strictTypes);

        return $this;
    }

    public function setFinal(bool $final = true): self
    {
        $this->classType->setFinal($final);

        return $this;
    }

    public function setReadOnly(bool $readonly = true): self
    {
        $this->classType->setReadOnly($readonly);

        return $this;
    }

    public function setAbstract(bool $abstract = true): self
    {
        $this->classType->setAbstract($abstract);

        return $this;
    }

    public function setFileComment(?string $comment): self
    {
        $this->file->setComment($comment);

        return $this;
    }

    public function setClassComment(?string $comment): self
    {
        $this->classType->setComment($comment);

        return $this;
    }

    public function addImplement(string $interface): self
    {
        $this->classType->addImplement($interface);

        return $this;
    }

    public function addExtend(string $class): self
    {
        $this->classType->setExtends($class);

        return $this;
    }

    public function addTrait(string $trait): self
    {
        $this->classType->addTrait($trait);

        return $this;
    }

    public function setNamespace(string $namespace): self
    {
        $this->namespace = $namespace;

        return $this;
    }

    public function getClassName(): string
    {
        return $this->namespace . '\\' . $this->classType->getName();
    }

    public function print(): string
    {
        if (! $this->namespace) {
            throw GenerationException::needsNamespace();
        }

        $printer = new PsrPrinter();

        $file = clone $this->file;
        $namespace = $file->addNamespace($this->namespace);
        $namespace->add($this->classType);

        $file = $this->simplifyClassNames($file);
        $code = $printer->printFile($file);

        foreach ($this->manipulations as $manipulation) {
            $code = (string) $manipulation(str($code));
        }

        $updatedFile = $file->fromCode($code);
        $updatedFile = $this->simplifyClassNames($updatedFile);

        return $printer->printFile($updatedFile);
    }
}
