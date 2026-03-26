<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    protected $fillable = ['name', 'price', 'stock', 'category_id'];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id'); // Nombre correcto del modelo
    }

    public function attributes()
    {
        return $this->belongsToMany(Attribute::class, 'product_attributes', 'product_id', 'atributo_id')->withPivot('valor');
    }

}
