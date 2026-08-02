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
        $this->checkRequirements();

        $currentVersion = $this->getLocalVersion();
        $remoteVersion = $this->getRemoteVersion();

        if ($currentVersion === $remoteVersion) {
            echo "Déjà à jour (commit: $remoteVersion)\n";
            return;
        }

        echo "Mise à jour disponible : $currentVersion -> $remoteVersion\n";

        $tarGzPath = $this->downloadTarball();
        $this->extractTarball($tarGzPath);
        $this->saveVersion($remoteVersion);

        unlink($tarGzPath);
        
        include 'updater_sql.php';

        echo "Installation terminée avec succès.\n";
    }

    /**
     * Vérifie que les extensions nécessaires sont présentes
     */
    private function checkRequirements(): void
    {
        if (!extension_loaded('zlib')) {
            throw new RuntimeException("L'extension 'zlib' n'est pas installée.");
        }
        if (!class_exists('PharData')) {
            throw new RuntimeException("La classe 'PharData' (extension Phar) n'est pas disponible.");
        }
    }

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
            throw new RuntimeException("Impossible de contacter l'API GitHub. Sur Windows essayer avec updater.bat");
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
     * Télécharge le tarball .tar.gz de la branche (au lieu du .zip)
     */
    private function downloadTarball(): string
    {
        $url = "https://github.com/{$this->owner}/{$this->repo}/archive/refs/heads/{$this->branch}.tar.gz";
        $tarGzPath = sys_get_temp_dir() . '/' . uniqid('gh_') . '.tar.gz';

        echo "Téléchargement depuis : $url\n";

        $opts = [
            'http' => [
                'header' => "User-Agent: PHP-Installer\r\n",
                'follow_location' => 1
            ]
        ];
        $context = stream_context_create($opts);

        // Utilisation de flux pour éviter de charger tout le fichier en mémoire
        $in = fopen($url, 'rb', false, $context);
        if ($in === false) {
            throw new RuntimeException("Échec du téléchargement du tarball.");
        }

        $out = fopen($tarGzPath, 'wb');
        stream_copy_to_stream($in, $out);
        fclose($in);
        fclose($out);

        return $tarGzPath;
    }

    /**
     * Extrait le .tar.gz avec PharData (zlib en interne, pas besoin de ext-zip)
     */
    private function extractTarball(string $tarGzPath): void
    {
        $tempExtractDir = sys_get_temp_dir() . '/' . uniqid('extract_');
        mkdir($tempExtractDir, 0755, true);

        try {
            // PharData décompresse le .gz puis lit le .tar
            $phar = new PharData($tarGzPath);

            // Décompression du .tar.gz -> génère un fichier .tar temporaire
            $tarPath = $tempExtractDir . '/archive.tar';
            $phar->decompress(); // crée un fichier .tar à côté du .tar.gz original

            // Le fichier .tar généré porte le même nom sans .gz
            $generatedTar = substr($tarGzPath, 0, -3); // enlève ".gz"

            $tarPhar = new PharData($generatedTar);
            $tarPhar->extractTo($tempExtractDir, null, true);

            unlink($generatedTar);

        } catch (Exception $e) {
            $this->removeDirectory($tempExtractDir);
            throw new RuntimeException("Erreur lors de l'extraction : " . $e->getMessage());
        }

        // GitHub crée un sous-dossier du type "repo-branch" ou "repo-sha"
        $extractedFolders = glob($tempExtractDir . '/*', GLOB_ONLYDIR);
        $sourceDir = $extractedFolders[0] ?? $tempExtractDir;

        if (!is_dir($this->installDir)) {
            mkdir($this->installDir, 0755, true);
        }

        $this->syncFiles($sourceDir, $this->installDir);
        $this->removeDirectory($tempExtractDir);
    }

    /**
     * Copie les fichiers en préservant certains éléments
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
        if (!is_dir($dir)) return;
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
    if ($_SERVER['HTTP_HOST'] !== 't2aw.lansard.ch') {
        $installer = new GitHubZipInstaller(
            owner: 'LightMan74',
            repo: 't2aw',
            installDir: __DIR__,
            branch: 'main'
        );

        $installer->run();
    }
} catch (Exception $e) {
    echo "ERREUR : " . $e->getMessage() . "\n";
}