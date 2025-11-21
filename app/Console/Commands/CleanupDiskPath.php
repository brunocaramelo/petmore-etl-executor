<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupDiskPath extends Command
{
    protected $signature = 'clean:storage-disk
                        {--disk= : Nome do disk em filesystems.php}
                        {--path= : Caminho dentro do disco}';

    protected $description = 'Lista recursivamente todos os arquivos de um caminho em um disk e remove todos.';

    public function handle()
    {
        $path = $this->option('path');
        $disk = $this->option('disk');

        $storage = Storage::disk($disk);

        if (!$storage->exists($path)) {
            $this->error("O caminho [$path] não existe no disk [$disk].");
            return 1;
        }

        $files = $storage->allFiles($path);

        if (empty($files)) {
            $this->info("Nenhum arquivo encontrado em [$path] no disk [$disk].");
            return 0;
        }

        $this->info("Arquivos encontrados:");
        foreach ($files as $file) {
            $this->line(" - $file");
        }

        if (!$this->confirm("Deseja realmente remover TODOS estes arquivos (".count($files).") ?")) {
            $this->info("Operação cancelada.");
            return 0;
        }

        $storage->delete($files);

        $this->info(count($files) . " arquivos removidos com sucesso!");

        return 0;
    }
}
