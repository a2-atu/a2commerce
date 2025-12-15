<?php

namespace App\Services\A2\Commerce;

use App\Models\A2\Commerce\A2Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ProductService
{
    /**
     * Get a featured product with optimized eager loading
     *
     * @return A2Product|null
     */
    public function getFeaturedProduct(): ?A2Product
    {
        return $this->withStandardRelations(
            A2Product::where('is_active', true)
        )->first();
    }

    /**
     * Get a product by ID or slug with optimized eager loading
     *
     * @param int|string $idOrSlug Product ID or slug
     * @return A2Product|null
     */
    public function getProductByIdOrSlug(int|string $idOrSlug): ?A2Product
    {
        $query = A2Product::where('is_active', true);

        // Try to find by ID first, then by slug
        if (is_numeric($idOrSlug)) {
            $query->where('id', $idOrSlug);
        } else {
            // Use findBySlug method from HasSlugs trait
            $product = A2Product::findBySlug($idOrSlug);
            if (!$product || !$product->is_active) {
                return null;
            }
            $query->where('id', $product->id);
        }

        return $this->withStandardRelations($query)->first();
    }

    /**
     * Get related products from similar subcategories or categories
     *
     * @param A2Product $product The product to find related products for
     * @param int $limit Maximum number of related products to return
     * @return Collection
     */
    public function getRelatedProducts(A2Product $product, int $limit = 6): Collection
    {
        // Get product's category IDs
        $categoryIds = $product->productTaxonomies
            ->where('type', 'category')
            ->pluck('taxonomy_id')
            ->toArray();

        if (empty($categoryIds)) {
            return collect([]);
        }

        // Get subcategories (child categories) of the product's categories
        $subcategoryIds = \App\Models\Vrm\Taxonomy::whereIn('parent_id', $categoryIds)
            ->where('type', 'category')
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        // Combine category IDs and subcategory IDs
        $allCategoryIds = array_unique(array_merge($categoryIds, $subcategoryIds));

        // Query for products in same categories/subcategories, excluding current product
        $query = A2Product::where('is_active', true)
            ->where('id', '!=', $product->id)
            ->whereHas('productTaxonomies', function ($q) use ($allCategoryIds) {
                $q->where('type', 'category')
                    ->whereIn('taxonomy_id', $allCategoryIds);
            });

        // Apply standard relations
        $query = $this->withStandardRelations($query);

        // Limit results
        return $query->limit($limit)->get();
    }

    /**
     * Get multiple products with optimized eager loading
     *
     * @param int|null $limit Maximum number of products to return
     * @param array $filters Additional filters to apply
     * @return Collection
     */
    public function getProducts(?int $limit = null, array $filters = []): Collection
    {
        $query = A2Product::query();

        // Apply filters
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['product_type'])) {
            $query->where('product_type', $filters['product_type']);
        }

        if (isset($filters['category_id'])) {
            $query->whereHas('productTaxonomies', function ($q) use ($filters) {
                $q->where('type', 'category')
                    ->where('taxonomy_id', $filters['category_id']);
            });
        }

        // Apply standard relations
        $query = $this->withStandardRelations($query);

        // Apply limit if specified
        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Apply standard eager loading relationships to a query
     * This optimizes queries by loading all necessary relationships upfront
     *
     * @param Builder $query
     * @return Builder
     */
    private function withStandardRelations(Builder $query): Builder
    {
        return $query->with([
            // Load meta data, excluding acquiring_price (admin-only field)
            'meta' => function ($metaQuery) {
                $metaQuery->where('key', '!=', 'acquiring_price');
            },
            // Load reviews for rating calculation
            'reviews',
            // Load all product taxonomies with taxonomy and its slugs (for breadcrumbs and filters)
            'productTaxonomies' => function ($ptQuery) {
                $ptQuery->with([
                    'taxonomy' => function ($taxonomyQuery) {
                        // Eager load slugs and parent for breadcrumb building
                        $taxonomyQuery->with(['slugs', 'parent']);
                    }
                ]);
            },
            // Load stock tracking variations (where taxonomy_id is null)
            'variations' => function ($variationQuery) {
                $variationQuery->whereNull('taxonomy_id');
            },
        ]);
    }
}
