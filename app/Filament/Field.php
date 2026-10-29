<?php

namespace App\Filament;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;

/**
 * Satu kolom data di SimpleResource: sekaligus isian form dan kolom tabel.
 * Contoh: Field::money('price', 'Harga')->required()
 */
final class Field
{
    private const TEXT = 'text';
    private const NUMBER = 'number';
    private const MONEY = 'money';
    private const DATE = 'date';
    private const ACTIVE = 'active';

    private bool $required = false;
    private bool $unique = false;
    private bool $searchable = false;
    private bool $sortable = false;
    private ?int $min = null;
    private ?string $unit = null;
    private ?string $hint = null;
    /** @var list<string> */
    private array $suggestions = [];
    private mixed $default = null;

    private function __construct(
        private readonly string $type,
        private readonly string $name,
        private readonly string $label,
    ) {}

    // --- jenis kolom ---

    public static function text(string $name, string $label): self
    {
        return new self(self::TEXT, $name, $label);
    }

    public static function number(string $name, string $label): self
    {
        return new self(self::NUMBER, $name, $label);
    }

    /** Rupiah: isian berawalan Rp, tabel berformat Rp1.000.000. */
    public static function money(string $name, string $label): self
    {
        return new self(self::MONEY, $name, $label)->min(0);
    }

    public static function date(string $name, string $label): self
    {
        return new self(self::DATE, $name, $label);
    }

    /** Sakelar Aktif/Nonaktif (kolom is_active), bisa diubah langsung dari tabel. */
    public static function active(): self
    {
        return new self(self::ACTIVE, 'is_active', 'Aktif')->default(true);
    }

    // --- pengaturan tambahan ---

    public function required(): self
    {
        $this->required = true;

        return $this;
    }

    /** Tidak boleh ada dua data dengan nilai yang sama. */
    public function unique(): self
    {
        $this->unique = true;

        return $this;
    }

    /** Bisa dicari lewat kotak pencarian tabel. */
    public function searchable(): self
    {
        $this->searchable = true;

        return $this;
    }

    /** Tabel bisa diurutkan dengan klik judul kolom. */
    public function sortable(): self
    {
        $this->sortable = true;

        return $this;
    }

    public function min(int $min): self
    {
        $this->min = $min;

        return $this;
    }

    /** Satuan di belakang angka, mis. "menit" atau "foto". */
    public function unit(string $unit): self
    {
        $this->unit = $unit;

        return $this;
    }

    /** Contoh isian yang tampil samar di dalam kotak. */
    public function hint(string $hint): self
    {
        $this->hint = $hint;

        return $this;
    }

    /**
     * Pilihan yang disarankan saat mengetik, tapi isian lain tetap boleh.
     *
     * @param list<string> $suggestions
     */
    public function suggestions(array $suggestions): self
    {
        $this->suggestions = $suggestions;

        return $this;
    }

    public function default(mixed $value): self
    {
        $this->default = $value;

        return $this;
    }

    // --- dipakai SimpleResource ---

    public function toFormComponent(): Component
    {
        $input = match ($this->type) {
            self::ACTIVE => Toggle::make($this->name),
            self::DATE => DatePicker::make($this->name),
            default => TextInput::make($this->name)
                ->numeric(in_array($this->type, [self::NUMBER, self::MONEY], strict: true))
                ->minValue($this->min)
                ->prefix($this->type === self::MONEY ? 'Rp' : null)
                ->suffix($this->unit)
                ->datalist($this->suggestions === [] ? null : $this->suggestions)
                ->placeholder($this->hint),
        };
        $input->label($this->label)->required($this->required)->default($this->default);

        if ($this->unique) {
            $input->unique(ignoreRecord: true);
        }

        return $input;
    }

    public function toColumn(): Column
    {
        if ($this->type === self::ACTIVE) {
            return ToggleColumn::make($this->name)->label($this->label);
        }

        $column = TextColumn::make($this->name)
            ->label($this->label)
            ->placeholder('-')
            ->searchable($this->searchable)
            ->sortable($this->sortable);

        return match ($this->type) {
            self::MONEY => $column->money('IDR', locale: 'id', decimalPlaces: 0),
            self::DATE => $column->date('D, d M Y'),
            default => $column->suffix($this->unit ? " {$this->unit}" : null),
        };
    }
}
