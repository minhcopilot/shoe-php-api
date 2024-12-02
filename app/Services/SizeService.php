<?php
namespace App\Services;

use App\Models\Size;

class SizeService
{
    public function getAllSizes()
    {
        return Size::all();
    }

    public function createSize($data)
    {
        return Size::create($data);
    }

    public function updateSize($id, $data)
    {
        $size = Size::findOrFail($id);
        $size->update($data);
        return $size;
    }

    public function deleteSize($id)
    {
        $size = Size::findOrFail($id);
        $size->delete();
        return $size;
    }
}
