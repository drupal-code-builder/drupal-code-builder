<?php

namespace DrupalCodeBuilder\PhpCsFixer;

class VirtualFileInfo extends \SplFileInfo
{
    public $pathVirtual   = '';
    public function __construct($pathVirtual)
    {
        $this->pathVirtual   = $pathVirtual;
    }

    public function __toString(): string
    {
        return $this->getRealPath();
    }

    public function getRealPath(): string
    {
        return $this->pathVirtual;
    }

    public function getATime(): int
    {
        return 0;
    }

    public function getBasename($suffix = null): string
    {
        return $this->getFilename();
    }

    public function getCTime(): int
    {
        return 0;
    }

    public function getExtension(): string
    {
        return '.php';
    }

    public function getFileInfo($className = null): \SplFileInfo
    {
        throw new \BadMethodCallException(sprintf('Method "%s" is not implemented.', __METHOD__));
    }

    public function getFilename(): string
    {
        return basename($this->pathVirtual);
    }

    public function getGroup(): int
    {
        return 0;
    }

    public function getInode(): int
    {
        return 0;
    }

    public function getLinkTarget(): string
    {
        return '';
    }

    public function getMTime(): int
    {
        return 0;
    }

    public function getOwner(): int
    {
        return 0;
    }

    public function getPath(): string
    {
        return '';
    }

    public function getPathInfo($className = null): \SplFileInfo
    {
        throw new \BadMethodCallException(sprintf('Method "%s" is not implemented.', __METHOD__));
    }

    public function getPathname(): string
    {
        return $this->getFilename();
    }

    public function getPerms(): int
    {
        return 0;
    }

    public function getSize(): int
    {
        return 0;
    }

    public function getType(): string
    {
        return 'file';
    }

    public function isDir(): bool
    {
        return false;
    }

    public function isExecutable(): bool
    {
        return false;
    }

    public function isFile(): bool
    {
        return true;
    }

    public function isLink(): bool
    {
        return false;
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function openFile($openMode = 'r', $useIncludePath = false, $context = null): \SplFileObject
    {
        throw new \BadMethodCallException(sprintf('Method "%s" is not implemented.', __METHOD__));
    }

    public function setFileClass($className = null): void
    {
    }

    public function setInfoClass($className = null): void
    {
    }
}