<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    use HasFactory;

    protected $fillable = ['product_size_id', 'name', 'status'];

    public function productSize()
    {
        return $this->belongsTo(ProductSize::class);
    }

    public function recipeIngredients()
    {
        return $this->hasMany(RecipeIngredient::class);
    }

    public function ingredients()
    {
        return $this->recipeIngredients();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
