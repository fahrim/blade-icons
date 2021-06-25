<?php

declare(strict_types=1);

namespace BladeUI\Icons\Generation;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

final class IconGenerator
{
    private Filesystem $filesystem;

    private array $sets;

    public function __construct(array $sets)
    {
        $this->filesystem = new Filesystem();
        $this->sets = $sets;
    }

    public static function create(array $config): self
    {
        return new self($config);
    }

    public function generate(): void
    {
        foreach ($this->sets as $set) {
            $destination = $this->getDestinationDirectory($set);

            foreach ($this->filesystem->files($set['source']) as $file) {
                $filename = Str::of($file->getFilename());
                $filename = $this->applyPrefixes($set, $filename);
                $pathname = $destination.$filename;

                $this->filesystem->copy($file->getRealPath(), $pathname);

                if (is_callable($set['after'] ?? null)) {
                    $set['after']($pathname, $set);
                }
            }
        }
    }

    private function getDestinationDirectory(array $set): string
    {
        $destination = Str::finish($set['destination'], DIRECTORY_SEPARATOR);

        if (! Arr::get($set, 'safe', false)) {
            $this->filesystem->deleteDirectory($destination);
        }

        $this->filesystem->ensureDirectoryExists($destination);

        return $destination;
    }

    private function applyPrefixes($set, Stringable $filename): Stringable
    {
        if ($set['input-prefix'] ?? false) {
            $filename = $filename->after($set['input-prefix']);
        }

        if ($set['output-prefix'] ?? false) {
            $filename = $filename->prepend($set['output-prefix']);
        }

        return $filename;
    }
}