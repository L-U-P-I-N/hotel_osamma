<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Floor extends Model
{
    protected $fillable = ['floor_number', 'door_count', 'name'];

    public function rooms()
    {
        return $this->hasMany(Room::class, 'floor', 'floor_number');
    }

    public function getUsedDoorsCountAttribute(): int
    {
        return $this->rooms()
            ->selectRaw('REGEXP_REPLACE(room_number, "[^0-9]", "") as door_num')
            ->get()
            ->pluck('door_num')
            ->unique()
            ->count();
    }

    public function validRoomNumbers(): array
    {
        $numbers = [];
        for ($i = 1; $i <= $this->door_count; $i++) {
            $numbers[] = (string)($this->floor_number * 100 + $i);
        }
        return $numbers;
    }

    public function isValidRoomNumber(string $roomNumber): bool
    {
        $doorNumber = (int)$roomNumber - ($this->floor_number * 100);
        return $doorNumber >= 1 && $doorNumber <= $this->door_count;
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name ?? 'الطابق ' . $this->floor_number;
    }

    public function getRangeAttribute(): string
    {
        $min = $this->floor_number * 100 + 1;
        $max = $this->floor_number * 100 + $this->door_count;
        return $min . ' - ' . $max;
    }
}
