<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Formatter;

class Result
{
    /** @var array<string, File>|File[] */
    public $files = [];

    /**
     * @return static
     */
    public function add(self $result): self
    {
        foreach ($result->files as $filename => $file) {
            if (count($file->violations) === 0) {
                continue;
            }

            $filename = self::removeRootPath($filename);

            if (array_key_exists($filename, $this->files)) {
                $this->files[$filename]->add($file);
                continue;
            }

            $this->files[$filename] = $file;
        }

        return $this;
    }

    private static function removeRootPath(string $path): string
    {
        if (strpos($path, PHPCSTD_ROOT) === 0) {
            return substr($path, strlen(PHPCSTD_ROOT));
        }

        return $path;
    }
}
