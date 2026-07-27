<?php

class GitHubZipInstaller
{
    private string $owner;
    private string $repo;
    private string $branch;
    private string $installDir;

    public function __construct(string $owner, string $repo, string $installDir, string $branch = 'main')
    {
        $this->owner = $owner;
        $this->repo = $repo;
        $this->branch = $branch;
        $this->installDir = rtrim($installDir, '/');
    }

    public function run(): void
    {
        $currentVersion = $this->getLocalVersion();
        $remoteVersion = $this->getRemoteVersion();

        if ($currentVersion === $remoteVersion) {
            echo "Déjà à jour (commit: $remoteVersion)\n";
            return;
        }

        echo "Mise à jour disponible : $currentVersion -> $remoteVersion\n";

        $zipPath = $this->downloadZip();
        $this->extractZip($zipPath);
        $this->saveVersion($remoteVersion);

        unlink($zipPath);

        echo "Installation terminée avec succès.\n";
    }

    /**
     * Récupère le dernier commit SHA via l'API GitHub
     */
    private function getRemoteVersion(): string
    {
        $url = "https://api.github.com/repos/{$this->owner}/{$this->repo}/commits/{$this->branch}";

        $opts = [
            'http' => [
                'header' => "User-Agent: PHP-Installer\r\n"
            ]
        ];

        $context = stream_context_create($opts);
        $response = file_get_contents($url, false, $context);

        if ($response === false) {
            throw new RuntimeException("Impossible de contacter l'API GitHub.");
        }

        $data = json_decode($response, true);
        return $data['sha'] ?? throw new RuntimeException("Réponse API invalide.");
    }

    private function getLocalVersion(): ?string
    {
        $file = $this->installDir . '/.version';
        return file_exists($file) ? trim(file_get_contents($file)) : null;
    }

    private function saveVersion(string $sha): void
    {
        file_put_contents($this->installDir . '/.version', $sha);
    }

    /**
     * Télécharge l'archive ZIP de la branche
     */
    private function downloadZip(): string
    {
        $url = "https://github.com/{$this->owner}/{$this->repo}/archive/refs/heads/{$this->branch}.zip";
        $zipPath = sys_get_temp_dir() . '/' . uniqid('gh_') . '.zip';

        echo "Téléchargement depuis : $url\n";

        $opts = [
            'http' => [
                'header' => "User-Agent: PHP-Installer\r\n"
            ]
        ];
        $context = stream_context_create($opts);

        $content = file_get_contents($url, false, $context);
        if ($content === false) {
            throw new RuntimeException("Échec du téléchargement du ZIP.");
        }

        file_put_contents($zipPath, $content);
        return $zipPath;
    }

    /**
     * Extrait le ZIP en préservant certains fichiers
     */
    private function extractZip(string $zipPath): void
    {
        $zip = new ZipArchive();

        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException("Impossible d'ouvrir le fichier ZIP.");
        }

        $tempExtractDir = sys_get_temp_dir() . '/' . uniqid('extract_');
        mkdir($tempExtractDir, 0755, true);

        $zip->extractTo($tempExtractDir);
        $zip->close();

        // Le zip GitHub crée un sous-dossier du type "repo-branch"
        $extractedFolders = glob($tempExtractDir . '/*', GLOB_ONLYDIR);
        $sourceDir = $extractedFolders[0] ?? $tempExtractDir;

        if (!is_dir($this->installDir)) {
            mkdir($this->installDir, 0755, true);
        }

        $this->syncFiles($sourceDir, $this->installDir);
        $this->removeDirectory($tempExtractDir);
    }

    /**
     * Copie les fichiers en préservant certains éléments (config, uploads...)
     */
    private function syncFiles(string $source, string $dest): void
    {
        $preserve = ['configuserlogin.php', '.gitignore', '.git', '.version'];

        foreach (scandir($source) as $item) {
            if ($item === '.' || $item === '..') continue;
            if (in_array($item, $preserve) && file_exists("$dest/$item")) continue;

            $srcPath = "$source/$item";
            $destPath = "$dest/$item";

            if (is_dir($srcPath)) {
                if (!is_dir($destPath)) mkdir($destPath, 0755, true);
                $this->syncFiles($srcPath, $destPath);
            } else {
                copy($srcPath, $destPath);
            }
        }
    }

    private function removeDirectory(string $dir): void
    {
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = "$dir/$item";
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}

// UTILISATION
try {
    if($_SERVER['HTTP_HOST']!='t2aw.lansard.ch'){
        $installer = new GitHubZipInstaller(
            owner: 'LightMan74',
            repo: 't2aw',
            installDir: __DIR__ ,
            branch: 'main'
        );

        $installer->run();
    }
} catch (Exception $e) {
    echo "ERREUR : " . $e->getMessage() . "\n";
}