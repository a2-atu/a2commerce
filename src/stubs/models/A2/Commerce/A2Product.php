<?php

namespace App\Models\A2\Commerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Vrm\Model\HasSlugs;

class A2Product extends Model
{
    use HasFactory, SoftDeletes, HasSlugs;

    protected $table = 'a2_ec_products';

    protected $fillable = [
        'name',
        'price',
        'product_type',
        'is_active',
        'is_auction',
        'is_service',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_auction' => 'boolean',
        'is_service' => 'boolean',
    ];

    // Relationships
    public function meta()
    {
        return $this->hasMany(A2ProductMeta::class, 'product_id');
    }

    public function variations()
    {
        return $this->hasMany(A2ProductVariation::class, 'product_id');
    }

    public function taxonomies()
    {
        return $this->belongsToMany(
            \App\Models\Vrm\Taxonomy::class,
            'a2_ec_product_taxonomies',
            'product_id',
            'taxonomy_id'
        )->withPivot('type')->withTimestamps();
    }

    public function productTaxonomies()
    {
        return $this->hasMany(A2ProductTaxonomy::class, 'product_id');
    }

    public function cache()
    {
        return $this->hasOne(A2ProductCache::class, 'product_id');
    }

    public function reviews()
    {
        return $this->hasMany(A2ProductReview::class, 'product_id');
    }

    public function wishlist()
    {
        return $this->hasMany(A2Wishlist::class, 'product_id');
    }

    public function comparisonItems()
    {
        return $this->hasMany(A2ComparisonItem::class, 'product_id');
    }

    public function reservedStock()
    {
        return $this->hasMany(A2ReservedStock::class, 'product_id');
    }

    public function orderItems()
    {
        return $this->hasMany(A2OrderItem::class, 'product_id');
    }

    public function auctions()
    {
        return $this->hasMany(A2Auction::class, 'product_id');
    }

    public function serviceLogs()
    {
        return $this->hasMany(A2ServiceLog::class, 'service_id');
    }

    public function inventoryEvents()
    {
        return $this->hasMany(A2InventoryEvent::class, 'product_id');
    }

    // Meta helper methods
    public function setMeta($key, $value)
    {
        return $this->meta()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    public function getMeta($key, $default = null)
    {
        $meta = $this->meta()->where('key', $key)->first();
        return $meta ? $meta->value : $default;
    }

    public function deleteMeta($key)
    {
        return $this->meta()->where('key', $key)->delete();
    }

    /* -------------------------------------------------------------------------------- */
    // Slug Methods
    /* -------------------------------------------------------------------------------- */

    /**
     * Define which field should be used for generating slugs.
     *
     * @return string
     */
    public function getSluggableField()
    {
        return 'name';
    }

    /**
     * Enable automatic slug updates for this model.
     *
     * @return bool
     */
    public function shouldAutoUpdateSlug()
    {
        // Development: Allow automatic updates
        if (app()->environment('local', 'development')) {
            return false;
        }

        // Production: Require manual approval
        return config('vormia.auto_update_slugs', false);
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Retrieve the model for a bound value.
     *
     * @param  mixed  $value
     * @param  string|null  $field
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if ($field === 'slug' || $field === null) {
            return static::findBySlug($value);
        }

        return parent::resolveRouteBinding($value, $field);
    }
}
