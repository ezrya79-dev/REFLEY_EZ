<?php

namespace App\Contracts;

use App\Data\ProbeResult;

/**
 * Sonde de connexion SMTP — interface pour pouvoir simuler le réseau en test.
 * L'action « tester la connexion » est strictement en lecture : elle ne
 * persiste rien et n'envoie aucun message.
 */
interface SmtpProbe
{
    public function probe(string $host, int $port, int $timeoutSeconds = 5): ProbeResult;
}
