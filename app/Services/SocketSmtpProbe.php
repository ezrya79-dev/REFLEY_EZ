<?php

namespace App\Services;

use App\Contracts\SmtpProbe;
use App\Data\ProbeResult;

/**
 * Implémentation réseau réelle : ouvre une socket vers le serveur SMTP et
 * vérifie la bannière 220. Suffisant pour diagnostiquer hôte/port/pare-feu
 * sans jamais envoyer d'e-mail ni d'identifiants.
 */
class SocketSmtpProbe implements SmtpProbe
{
    public function probe(string $host, int $port, int $timeoutSeconds = 5): ProbeResult
    {
        $errno = 0;
        $errstr = '';

        $socket = @fsockopen($host, $port, $errno, $errstr, $timeoutSeconds);

        if ($socket === false) {
            return ProbeResult::failure($errstr !== '' ? $errstr : 'Connection failed (errno '.$errno.').');
        }

        stream_set_timeout($socket, $timeoutSeconds);
        $banner = (string) fgets($socket, 512);
        fclose($socket);

        if (str_starts_with($banner, '220')) {
            return ProbeResult::success(trim($banner));
        }

        return ProbeResult::failure($banner === '' ? 'No SMTP banner received.' : trim($banner));
    }
}
