<?php

declare(strict_types=1);

namespace Hei\SocialBu\Data;

final readonly class ResolvedFile
{
    /**
     * @param  resource|null  $stream
     */
    public function __construct(
        public string $name,
        public string $mimeType,
        public int $size,
        public mixed $stream,
        public string $path,
    ) {}

    public function __destruct()
    {
        $this->close();
    }

    /**
     * Close the stream if open.
     */
    public function close(): void
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
    }
}
