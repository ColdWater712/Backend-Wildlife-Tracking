<?php

namespace App\Services\Ai;

use App\Models\Species;
use Illuminate\Support\Collection;

/** Mencocokkan label bebas dari model AI ke data master spesies. */
class SpeciesMatcher
{
    /** Sinonim umum (ID/EN/latin) -> kata kunci yang muncul di nama spesies. */
    private const ALIASES = [
        'orangutan' => ['orangutan', 'orang utan', 'pongo', 'great ape', 'ape'],
        'harimau' => ['harimau', 'tiger', 'panthera tigris', 'macan', 'tigris'],
        'gajah' => ['gajah', 'elephant', 'elephas'],
        'badak' => ['badak', 'rhino', 'rhinoceros', 'dicerorhinus'],
        'rusa' => ['rusa', 'deer', 'sambar', 'cervus', 'rusa unicolor'],
        'beruang' => ['beruang', 'bear', 'helarctos', 'sun bear'],
        'siamang' => ['siamang', 'gibbon', 'symphalangus', 'owa'],
        'kukang' => ['kukang', 'slow loris', 'nycticebus'],
        'trenggiling' => ['trenggiling', 'pangolin', 'manis javanica'],
        'tapir' => ['tapir', 'tapirus'],
        'rangkong' => ['rangkong', 'enggang', 'hornbill', 'buceros'],
        'beruk' => ['beruk', 'macaque', 'macaca', 'monyet'],
    ];

    /** @var Collection<int, Species>|null */
    private ?Collection $species = null;

    public function all(): Collection
    {
        return $this->species ??= Species::query()->get();
    }

    public function match(?string ...$labels): ?Species
    {
        $labels = array_values(array_filter(array_map(
            fn ($l) => $l === null ? null : mb_strtolower(trim($l)),
            $labels,
        )));

        if ($labels === []) {
            return null;
        }

        $species = $this->all();

        // 1. Kecocokan persis nama ilmiah / nama umum
        foreach ($labels as $label) {
            $hit = $species->first(fn (Species $s) => mb_strtolower($s->scientific_name) === $label
                || mb_strtolower($s->common_name) === $label);
            if ($hit) {
                return $hit;
            }
        }

        // 2. Substring dua arah
        foreach ($labels as $label) {
            if (in_array($label, ['unknown', 'none', 'tidak diketahui', ''], true)) {
                continue;
            }
            $hit = $species->first(fn (Species $s) => str_contains(mb_strtolower($s->common_name), $label)
                || str_contains(mb_strtolower($s->scientific_name), $label)
                || str_contains($label, mb_strtolower($s->scientific_name)));
            if ($hit) {
                return $hit;
            }
        }

        // 3. Lewat kamus sinonim
        foreach ($labels as $label) {
            foreach (self::ALIASES as $keyword => $aliases) {
                foreach ($aliases as $alias) {
                    if (! str_contains($label, $alias)) {
                        continue;
                    }
                    $hit = $species->first(fn (Species $s) => str_contains(mb_strtolower($s->common_name), $keyword)
                        || collect($aliases)->contains(fn ($a) => str_contains(mb_strtolower($s->scientific_name), $a)));
                    if ($hit) {
                        return $hit;
                    }
                }
            }
        }

        return null;
    }

    /** Daftar spesies untuk konteks prompt AI. */
    public function promptList(): string
    {
        return $this->all()
            ->map(fn (Species $s) => "- {$s->common_name} ({$s->scientific_name})")
            ->implode("\n");
    }
}
