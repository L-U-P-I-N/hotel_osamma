<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Floor extends Model
{
    protected $fillable = ['floor_number', 'door_count', 'name'];

    public function roomNumbers(): array
    {
        $numbers = [];
        for ($i = 1; $i <= $this->door_count; $i++) {
            $numbers[] = (string)($this->floor_number * 100 + $i);
        }
        return $numbers;
    }

    public function rooms()
    {
        return $this->hasMany(Room::class, 'floor', 'floor_number');
    }
}
