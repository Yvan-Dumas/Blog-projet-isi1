<?php

/**
 * Class Logger pour l'écriture de logs pour l'application.
 * Implémenté selon le Singleton pour garantir l'instance unique.
 */
class Logger
{
    // Instance unique du logger
    private static ?Logger $instance = null;

    // Tableau contenant les chemins des dossiers logs en fonction du type (info, erreur, sécurité)
    private array $paths;

    private function __construct()
    {
        $basePath = __DIR__ . '/../logs/'; // Chemin vers le dossier logs
        $this->paths = [
            'app' => $basePath . 'app/',
            'error' => $basePath . 'error/',
            'security' => $basePath . 'security/',
        ];
    }

    // Pour empêcher le clonage
    private function __clone()
    {
    }

    // Pour obtenir l'instance unique
    public static function getInstance(): Logger
    {
        if (self::$instance === null) {
            self::$instance = new Logger();
        }
        return self::$instance;
    }

    // Log applicatif standard
    public function info(string $message, array $context = []): void
    {
        $this->write('INFO', $message, $context, 'app');
    }

    // Log de sécurité (auth, accès interdit…)
    public function security(string $message, array $context = []): void
    {
        $this->write('SECURITY', $message, $context, 'security');
    }

    // Log d’erreur
    public function error(string $message, array $context = []): void
    {
        $this->write('ERROR', $message, $context, 'error');
    }

    // Méthode d’écriture dans les fichiers de log
    private function write(
        string $level,
        string $message,
        array $context,
        string $type
    ): void {
        $dateTime = date('Y-m-d H:i:s'); // Date et heure pour la ligne du log
        $fileDate = date('Y-m-d'); // Utilisée pour le nom du fichier

        $contextString = empty($context)
            ? ''
            : json_encode($context, JSON_UNESCAPED_UNICODE); // On transforme le tableau en json 

        // Construction de la ligne de log finale
        $logLine = "[$dateTime] [$level] $message $contextString" . PHP_EOL;

        // Vérification et création du dossier si inexistant
        $logDir = $this->paths[$type];
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        // Ecriture dans le fichier
        file_put_contents(
            $logDir . "$type-$fileDate.log",
            $logLine,
            FILE_APPEND
        );
    }
}
