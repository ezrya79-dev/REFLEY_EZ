<?php

namespace App\Data;

/** Résultat immuable d'un test de connexion à un service externe. */
final readonly class ProbeResult
{
    public function __construct(
        public bool $ok,
        public string $message,
    ) {}

    public static function success(string $message): self
    {
        return new self(true, $message);
    }

    public static function failure(string $message): self
    {
        return new self(false, $message);
    }
}
