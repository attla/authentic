<?php

namespace Attla\Authentic\Commands;

use Attla\Authentic\Ability;
use Illuminate\Filesystem\Filesystem;

class CacheAbilities extends \Illuminate\Console\Command
{
    /**
     * The console command name
     *
     * @var string
     */
    protected $signature = 'cache:abilities';

    /**
     * The console command description
     *
     * @var string
     */
    protected $description = 'Create a cache of available abilities.';

    /**
     * The filesystem instance
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new route command instance
     *
     * @param \Illuminate\Filesystem\Filesystem $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    /**
     * Execute the console command
     *
     * @return void
     */
    public function handle()
    {
        $this->files->put(Ability::cachePath(), $this->content());

        $this->components->info('Ability list cached!');
    }

    /**
     * Build the cache content
     *
     * @return string
     */
    protected function content()
    {
        return '<?php return [' . implode(', ',
        array_map(fn($item) => "'" . addslashes($item) . "'", Ability::compile())
        ) . '];';
    }
}
