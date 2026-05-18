<?php

namespace App\View\Components;

use Illuminate\View\Component;

class ViteManifestLoader extends Component
{
    public array $assets;
    public array $cssFiles = [];
    public array $jsFiles = [];

    public function __construct($assets = [])
    {
        $this->assets = $assets;
        $this->loadFromManifest();
    }

    private function loadFromManifest(): void
    {
        $manifestPath = public_path('build/manifest.json');
        
        if (!file_exists($manifestPath)) {
            return;
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);

        foreach ($this->assets as $asset) {
            if (isset($manifest[$asset])) {
                $entry = $manifest[$asset];

                // Ajouter le fichier CSS
                if (str_ends_with($asset, '.css')) {
                    $this->cssFiles[] = 'build/' . $entry['file'];
                } 
                // Ajouter le fichier JS
                elseif (str_ends_with($asset, '.js')) {
                    $this->jsFiles[] = $this->prefixUrl('build/' . $entry['file']);

                    // Ajouter les CSS associés (générés par Vite)
                    if (!empty($entry['css'])) {
                        foreach ($entry['css'] as $css) {
                            $this->cssFiles[] = 'build/' . $css;
                        }
                    }
                }
            }
        }

        // Dédupliquer les CSS
        $this->cssFiles = array_unique($this->cssFiles);
    }

    private function prefixUrl(string $path): string
    {
        return $path;
    }

    public function render()
    {
        return view('components.vite-manifest-loader');
    }
}
